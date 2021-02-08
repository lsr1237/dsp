<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/10/14
 * Time: 11:08
 */

namespace common\services;


use common\bases\CommonService;
use common\extend\Tool;
use common\extend\wx\AppletConfig;
use common\extend\wx\AppletPay;
use Yii;

class WxCheckService extends CommonService
{
    const APPID = 'wx9d4508020ba7f1a4';
    const QUERY_APPID = 'wx125a92022bafcb97'; // 查询用户App_id
    const APPSECRET = 'c6e04e8b29da892d46766b824eb41113';
    const EXP_TOKEN = 6600; // 微信access_toekn过期时间一小时五十分钟
    const EXP_TICKET = 42600; // 微信ticket过期时间十一小时五十分钟
    const TICKET_UPDATE_TIME = 600; // 微信更新ticket间隔时间10分钟
    const ENCODING_AES_KEY = '1234567891234567891234567891234567891234568'; // 微信消息加解密Key
    const TOKEN = '123456'; // 消息校验Token
    const REFRESH_TOKEN = 'refreshtoken@@@U56MseUduiwcSK15FfaTVaZLseHP_ugXlOf8GPWWxVw'; // 刷新token
    const SUCCESS_CODE = '0'; // 成功返回状态码
    const TOKEN_API = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token'; // 微信获取token接口
    const CHECK_API = 'https://api.weixin.qq.com/cgi-bin/wxverify/checkwxverifynickname?access_token='; // 微信认证名称检测接口
    const AUTH_CODE_API = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='; // 获取微信预授权码接口
    const CODE_INFO_API = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='; // 获取微信预授权码信息接口
    const AUTH_TOKEN_API = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='; // 获取微信接口调用令牌接口

    const ERROR_CODE_MAP = [
        '53011', // 名称检测命中频率限制
    ];

    /**
     * 获取component_access_token
     * @return array
     */
    public static function getComponentToken()
    {
        $ticket = RedisService::getKey(RedisService::WX_TICKET);
        if (!$ticket) {
            return [
                'status' => false,
                'msg' => '微信票据获取失败，请稍后重试'
            ];
        }
        $data = [
            'component_appid' => self::APPID,
            'component_appsecret' => self::APPSECRET,
            'component_verify_ticket' => $ticket
        ];
        $res = CurlService::sendRequest(self::TOKEN_API, json_encode($data));
        if ($res) {
            $resArr = json_decode($res, true);
            if ($resArr && isset($resArr['component_access_token'])) {
                return [
                    'status' => true,
                    'component_access_token' => $resArr['component_access_token']
                ];
            }
        }
        return [
            'status' => false,
            'msg' => '获取微信token失败'
        ];
    }

    /**
     * 获取预授权码
     * @param $token
     * @return array
     */
    public static function getAuthCode($token)
    {
        $data = [
            'component_appid' => self::APPID
        ];
        $res = CurlService::sendRequest(self::AUTH_CODE_API . $token, json_encode($data));
        if ($res) {
            $resArr = json_decode($res, true);
            if ($resArr && isset($resArr['pre_auth_code'])) {
                return [
                    'status' => true,
                    'pre_auth_code' => $resArr['pre_auth_code']
                ];
            }
        }
        return [
            'status' => false,
            'msg' => '获取微信授权码失败'
        ];
    }

    /**
     * 获取授权码信息
     * @param $token
     * @param $code
     * @return array
     */
    public static function getCodeInfo($token, $code)
    {
        $data = [
            'component_appid' => self::APPID,
            'authorization_code' => $code
        ];
        $res = CurlService::sendRequest(self::CODE_INFO_API . $token, json_encode($data));
        if ($res) {
            $resArr = json_decode($res, true);
            if ($resArr && isset($resArr['authorization_info']['authorizer_appid']) && isset($resArr['authorization_info']['authorizer_refresh_token'])) {
                return [
                    'status' => true,
                    'authorizer_appid' => $resArr['authorization_info']['authorizer_appid'],
                    'authorizer_refresh_token' => $resArr['authorization_info']['authorizer_refresh_token']
                ];
            }
        }
        return [
            'status' => false,
            'msg' => '获取微信授权码信息失败'
        ];
    }

    /**
     * 获取token
     * @param $token
     * @param $appid
     * @return array
     */
    public static function getToken($token)
    {
        $data = [
            'component_appid' => self::APPID,
            'authorizer_appid' => self::QUERY_APPID,
            'authorizer_refresh_token' => self::REFRESH_TOKEN,
        ];
        $res = CurlService::sendRequest(self::AUTH_TOKEN_API . $token, json_encode($data));
        if ($res) {
            $resArr = json_decode($res, true);
            if ($resArr) {
                if (isset($resArr['authorizer_access_token'])) {
                    RedisService::setKeyWithExpire(
                        RedisService::WX_ACCESS_TOKEN,
                        $resArr['authorizer_access_token'],
                        self::EXP_TOKEN
                    );
                    Yii::info('刷新token:' . $resArr['authorizer_refresh_token'], 'wxTicket1');
                }
                return [
                    'status' => true,
                    'authorizer_access_token' => $resArr['authorizer_access_token'],
                ];
            }
        }
        return [
            'status' => false,
            'msg' => '获取微信接口授权token失败'
        ];
    }

    /**
     * 名称检测
     * @param $name
     * @return array|string
     */
    public static function checkName($name)
    {
        $accessToken = RedisService::getKey(RedisService::WX_ACCESS_TOKEN);
        if (!$accessToken) {
            $componentToken = self::getComponentToken();
            if ($componentToken['status']) {
                $token = self::getToken(
                    $componentToken['component_access_token']
                );
                if (!$token['status']) {
                    return [
                        'code' => '',
                        'msg' => $token['msg']
                    ];
                }
            } else {
                return [
                    'code' => '',
                    'msg' => $componentToken['msg']
                ];
            }
            $accessToken = $token['authorizer_access_token'];
        }
        $data = [
            'nick_name' => $name,
        ];
        $res = CurlService::sendRequest(self::CHECK_API . $accessToken, json_encode($data, JSON_UNESCAPED_UNICODE));
        if ($res) {
            $resArr = json_decode($res, true);
            if (isset($resArr['errcode'])) {
                if ($resArr['errcode'] == self::SUCCESS_CODE) {
                    return [
                        'code' => $resArr['errcode'],
                        'msg' => '名称可使用'
                    ];
                }
                return [
                    'code' => $resArr['errcode'],
                    'msg' => self::codeFilter($resArr['errcode'])
                ];
            }
        }
        return [
            'code' => '',
            'msg' => '名称检测失败'
        ];
    }

    /**
     * 过滤状态码
     * @param $code
     * @return string
     */
    public static function codeFilter($code)
    {
        switch ($code) {
            case '0' :
                return '名称可使用';
                break;
            case '53010' :
                return '名称格式不合法';
                break;
            case '53011' :
                return '名称检测命中频率限制';
                break;
            case '53012' :
                return '禁止使用该名称';
                break;
            case '53013' :
                return '公众号：名称与已有公众号名称重复;小程序：该名称与已有小程序名称重复';
                break;
            case '53014' :
                return '公众号：公众号已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A};小程序：小程序已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A}';
                break;
            case '53015' :
                return '公众号：该名称与已有小程序名称重复，需与该小程序帐号相同主体才可申请;小程序：该名称与已有公众号名称重复，需与该公众号帐号相同主体才可申请';
                break;
            case '53016' :
                return '公众号：该名称与已有多个小程序名称重复，暂不支持申请;小程序：该名称与已有多个公众号名称重复，暂不支持申请';
                break;
            case '53017' :
                return '公众号：小程序已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A};小程序：公众号已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A}';
                break;
            case '53018' :
                return '名称命中微信号';
                break;
            case '53019' :
                return '  名称在保护期内';
                break;
            default:
                return '名称检测失败';
        }
    }

    /**
     * 发送订阅消息
     * @param string $toUser 消息接收这openid
     * @param string $name 名称
     * @param string $curCode 当前状态码
     */
    public static function sendMsg($toUser, $name, $curCode)
    {
        $applet = new AppletPay(AppletConfig::APPLET_FOUR);
        $accessToken = $applet->getAccessToken();
        if(mb_strlen($name) > 20) {
            $name = mb_substr($name, 0, 20);
        }
        $memo = self::codeFilter($curCode);
        if (mb_strlen($memo)) {
            $memo = mb_substr($memo, 0, 20);
        }
        $data = [
            'touser' => $toUser,
            'template_id' => '2gzEkkfgVKlIh6BM4JIwzgaEOZHzdCFmW_b5dnCVF4o',
            'page' => 'pages/monitor/index',
            'data' => [
                'thing1' => [
                    'value' => $name // 内容
                ],
                'thing3' => [
                    'value' => $memo // 备注
                ],
                'time2' => [
                    'value' => date('Y-m-d H:i:s') // 时间
                ]
            ]
        ];
        $applet->subscribeMessageSend($accessToken, $data);
    }
}