<?php

namespace common\bases;

use common\models\MobileCodeModel;
use common\models\MobileLogModel;
use common\services\JjSmsService;
use yii\base\Component;
use Yii;
use yii\helpers\Json;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * 服务类基类
 */
class CommonService extends Component
{
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILURE = 'FAILURE';
    const DING_TALK_DEPT = 'dept';
    const DING_TALK_COMPANY = 'company';
    const SYSTEM_ERR_MESSAGE = '系统繁忙，请重试';
    const USER_TOKEN_PREFIX = 'token_';
    const USER_TOKEN_TIME_OUT = 86400 * 7;
    const ERROR_TYPE_VIP = 'open_vip';

    /**
     * 字符串转数组
     *
     * @param string $string 字符串
     * @param string $delimiter 分隔符
     * @return array
     */
    public function stringToArray($string, $delimiter = ",")
    {
        if (strstr($string, $delimiter)) {
            $arr = explode($delimiter, $string);
        } else {
            $arr = [$string];
        }
        return $arr;
    }

    /**
     * 发送钉钉通知至电报群
     * @param string $msg 消息内容
     */
    public static function sendDingMsg($msg)
    {
        if (YII_ENV == 'dev') {
            $prefix = '预发环境';
        } else {
            $prefix = '生产环境';
        }
        $im = Yii::$app->companyim;
        $content = sprintf('%s%s-%s', Yii::$app->params['msg_sign'] ?? '', $prefix, $msg); // 发送内容
        $im->sendNotice('sys_error', $im::COMPANY_CHAT_ID, $content);
    }

    /**
     * 吉家短信服务
     * @param string $mobiles 手机号
     * @param string $smsType 短信类型（模板）
     * @param array $params 参数
     * @param string $type 类型
     * @param null|integer $loanId 借款ID
     * @return string
     */
    public static function sendMobileSms($mobiles, $smsType, $params, $type, $loanId = null)
    {
        if (empty($mobiles) or !preg_match("/^(1[0-9]{10},*)+$/", $mobiles)) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '请输入可用的手机号码'
            ]);
        }
        if ($type == MobileLogModel::TYPE_AUTHENTICATION_CODE) {
            if (!MobileCodeModel::addMobileCode($mobiles, $params[0])) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '发送短信失败，可能系统繁忙，请稍后再试'
                ]);
            }
        }
        try {
            $result = JjSmsService::sendSms($smsType, $mobiles, $params);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统错误，请稍后再试'
            ]);
        }  catch (Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统错误，请稍后再试'
            ]);
        }
        $mobileArr = explode(',', $mobiles);
        foreach ($mobileArr as $v) {
            MobileLogModel::add([
                'mobile' => $v,// 接收人手机号码
                'return_message' => Json::encode($result), // 返回内容
                'send_message' => implode('##', $params), // 参数内容
                'content' => $smsType, // 发送模板
                'type' => $type,
            ]);
        }
        if (!$result) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '发送失败，远端未响应'
            ]);
        }
        if ($result['result'] == JjSmsService::RESULT_SUCCESS) {
            return Json::encode([
                'status' => self::STATUS_SUCCESS,
                'error_message' => '发送成功'
            ]);
        }
        return Json::encode([
            'status' => self::STATUS_FAILURE,
            'error_message' => JjSmsService::resultFilter($result),
        ]);
    }
}