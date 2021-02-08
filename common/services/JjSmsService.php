<?php

namespace common\services;

use common\bases\CommonService;
use common\extend\Tool;
use Yii;

class JjSmsService
{
    const SIGN = '【短视频剪辑】';
    const API_HOST = 'api.movek.net:8513'; // 调用域名端口
    const API_SEND_SMS = '/sms/Api/Send.do'; // 下发短信接口
    const API_REPORT = '/sms/Api/report.do'; // 状态回执拉取接口
    const API_SEARCH_NUMBER = '/sms/Api/searchNumber.do'; // 余额短信条数查询接口
    const CONF_MAP = [
        'code' => [
            'SpCode' => '10410', // 企业编号
            'LoginName' => 'SDK-A10410-10410', // 用户名称
            'Password' => '@#44@Qa#', // 用户密码
        ], // 验证码账户
        'default_notify' => [
            'SpCode' => '10411', // 企业编号
            'LoginName' => 'SDK-A10411-10411', // 用户名称
            'Password' => 'c#Se67346', // 用户密码
        ], // 默认通知账户
    ];

    const TEMPLATE_REGISTER = 'register'; // 注册验证码
    const TEMPLATE_VERIFY = 'other_verify'; // 其他验证码
    const SMS_MAP = [
        'register' => self::TEMPLATE_REGISTER,
        'login' => self::TEMPLATE_VERIFY,
        'reset_pwd' => self::TEMPLATE_VERIFY,
    ];

    const RESULT_SUCCESS = '0'; // 发送成功

    /**
     * 发送短信
     * @param string $type 类型
     * @param string $mobile 手机号码
     * @param array $data 短信参数
     * @return bool|mixed
     */
    public static function sendSms($type, $mobile, $data)
    {
        $conf = self::getAccountInfo($type); // 获取账号配置信息
        $params = [
            'SpCode' => $conf['SpCode'], // 企业编号
            'LoginName' => $conf['LoginName'], // 用户名称
            'Password' => $conf['Password'], // 用户密码
            'MessageContent' => sprintf('%s%s', self::SIGN, self::getSmsContent($type, $data)), // 短信内容
            'UserNumber' => $mobile, // 短信内容
            'SerialNumber' => sprintf('%s%s', date('YmdHis'), Tool::getRandomNum(6)), // 流水号，20位数字，唯一
            'ScheduleTime' => '', // 预约发送时间，格式:yyyyMMddhhmmss，立即发送请填空
            'subPort' => '', // 可选，扩展号
        ]; // 接口参数
        $requestParams = http_build_query($params);
        $content = CurlService::sendRequest(sprintf('%s%s', self::API_HOST, self::API_SEND_SMS), $requestParams);
        if ($content) {
            parse_str($content, $result);
            return $result;
        }
        return false;
    }

    /**
     * 通知账户状态回执拉取接口
     * 周期：30秒调用一次，如果调用频繁会禁止调用
     * @param string $accountType 账户类型
     * @return bool
     */
    public static function report($accountType = 'notify')
    {
        $conf = self::CONF_MAP[$accountType]; // 获取账号配置信息
        $params = [
            'SpCode' => $conf['SpCode'], // 企业编号
            'LoginName' => $conf['LoginName'], // 用户名称
            'Password' => $conf['Password'], // 用户密码
        ]; // 接口参数
        $requestParams = http_build_query($params);
        $content = CurlService::sendRequest(sprintf('%s%s', self::API_HOST, self::API_REPORT), $requestParams);
        if ($content) {
            parse_str($content, $result);
            return $result;
        }
        return false;
    }

    /**
     * 余额短信条数查询接口
     * 周期：1分钟调用一次，如果调用频繁会禁止调用
     * @param string $accountType 账户类型
     * @return bool
     */
    public static function searchNumber($accountType = 'notify')
    {
        $conf = self::CONF_MAP[$accountType]; // 获取账号配置信息
        $params = [
            'SpCode' => $conf['SpCode'], // 企业编号
            'LoginName' => $conf['LoginName'], // 用户名称
            'Password' => $conf['Password'], // 用户密码
        ]; // 接口参数
        $requestParams = http_build_query($params);
        $content = CurlService::sendRequest(sprintf('%s%s', self::API_HOST, self::API_SEARCH_NUMBER), $requestParams);
        if ($content) {
            parse_str($content, $result);
            return $result;
        }
        return false;

    }

    /**
     * 返回短信内容
     * @param string $type 短信类型
     * @param array $params 短信参数
     * @return string 返回短信的具体内容
     */
    public static function getSmsContent($type, $params)
    {
        switch ($type) {
            case self::TEMPLATE_REGISTER: // 注册验证码
                {
                    return sprintf('你好，你正在注册成为新用户，验证码是%s，切勿告知他人。', $params[0]);
                }
            case self::TEMPLATE_VERIFY: // 其他验证码
                {
                    return sprintf('尊敬的用户，您本次验证码是：%s（有效期15分钟），请勿将验证码转告他人，如非本人操作，请忽略本短信。', $params[0]);
                }
        }
        return '';
    }

    /**
     * 短信结果过滤
     * @param object $result 返回结果
     * @return string
     */
    public static function resultFilter($result)
    {
        $msg = $result['description'];
        switch ($result['result']) {
            case 1: // 提交参数不能为空
            case 2: // 账号无效或未开户
            case 3: // 账号密码错误
            case 4: // 预约发送时间无效
            case 5: // IP不合法
            case 6: // 号码中含有无效号码或不在规定的号段
            case 7: // 内容中含有非法关键字
            case 8: // 内容长度超过上限，最大4000
            case 9: // 接受号码过多，最大5000
            case 12: // 您尚未订购[普通短信业务]，暂不能发送该类信息
            case 13: // 您的[普通短信业务]剩余数量发送不足，暂不能发送该类信息
            case 14: // 流水号格式不正确
            case 15: // 流水号重复
            case 17: // 余额不足
            case 18: // 扣费不成功
            case 21: // 您只能发送联通的手机号码，本次发送的手机号码中包含了非联通的手机号码
            case 22: // 您只能发送移动的手机号码，本次发送的手机号码中包含了非移动的手机号码
            case 23: // 您只能发送电信的手机号码，本次发送的手机号码中包含了非电信的手机号码
            case 24: // 账户状态不正常
            case 25: // 账户权限不足
            case 28: // 发送内容与模板不符
                {
                    $msg = '短信发送失败，请联系客服';
                    CommonService::sendDingMsg(sprintf('吉家短信发送失败：[%s]%s', $result['result'], $result['description']));
                    break;
                }
            case 10:
                {
                    $msg = '您的号码暂不支持短信服务，详情请联系客服';
                    CommonService::sendDingMsg(sprintf('吉家短信发送失败：[%s]%s', $result['result'], $result['description']));
                    break;
                }
            case 26: // 需要人工审核
                {
                    $msg = '短信发送中，请等待';
                    CommonService::sendDingMsg(sprintf('吉家短信发送结果：[%s]%s', $result['result'], $result['description']));
                    break;
                }
        }
        return $msg;
    }

    /**
     * 获取账户信息
     * @param string $type 短信类型
     * @return mixed
     */
    private static function getAccountInfo($type)
    {
        switch ($type) {
            case self::TEMPLATE_REGISTER: // 注册验证码
            case self::TEMPLATE_VERIFY: // 其他验证码
                {
                    $conf = self::CONF_MAP['code']; // 验证码账户
                    break;
                }
            default:
                {
                    $conf = self::CONF_MAP['default_notify']; // 默认通知账户
                    break;
                }
        }
        return $conf;
    }
}