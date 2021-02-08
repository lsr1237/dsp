<?php
namespace query\controllers;


use common\models\QueryUserModel;
use query\bases\ApiController;
use common\bases\CommonService;
use common\extend\wx\AppletConfig;
use common\extend\wx\AppletPay;
use common\services\RedisService;
use common\services\WxCheckService;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use common\extend\check\WXBizMsgCrypt;
use DOMDocument;

class WxController extends ApiController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => ['login', 'callback'],
                'allow' => true,
                'roles' => ['?'],
            ],
            // 其它的Action必须要授权用户才可访问
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ];
        return $behaviors;
    }

    /**
     * 微信授权事件接收
     * @return string
     */
    public function actionCallback()
    {
        $ticketTime = RedisService::ttl(RedisService::WX_TICKET);
        if ($ticketTime >= WxCheckService::TICKET_UPDATE_TIME) {
            return 'success';
        }
        $request = Yii::$app->request;
        $msgSign = $request->get('msg_signature', '');
        $timestamp = $request->get('timestamp', '');
        $nonce = $request->get('nonce', '');
        try {
            $encryptMsg = file_get_contents("php://input");
            if (!empty($encryptMsg)) {
                $pc = new WXBizMsgCrypt(WxCheckService::TOKEN, WxCheckService::ENCODING_AES_KEY, WxCheckService::APPID);
                $xml_tree = new DOMDocument();
                $xml_tree->loadXML($encryptMsg);
                $array_e = $xml_tree->getElementsByTagName('Encrypt');
                $encrypt = $array_e->item(0)->nodeValue;
                $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
                $from_xml = sprintf($format, $encrypt);
                $msg = '';
                $errCode = $pc->decryptMsg($msgSign, $timestamp, $nonce, $from_xml, $msg);
                Yii::error('解析数据：' . $msg, 'wxTicket');
                if ($errCode == 0) {
                    $xml = new DOMDocument();
                    $xml->loadXML($msg);
                    $e = $xml->getElementsByTagName('ComponentVerifyTicket');
                    $ticket = $e->item(0)->nodeValue;
                    if ($ticket) {
                        RedisService::setKeyWithExpire(RedisService::WX_TICKET, $ticket, WxCheckService::EXP_TICKET);
                        Yii::info(sprintf('微信ticket更新：%s', $ticket), 'wxTicket');
                    }
                } else {
                    Yii::error(sprintf('微信ticket解析错误，错误码：%s', $errCode), 'wxTicket');
                }
            }
        } catch (Exception $e) {
            Yii::error(sprintf('微信ticket接收异常，返回数据：%s', $e), 'wxTicket');
        }
        return 'success';
    }

    /**
     * 微信登陆-无手机号
     * @return string
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;
        $code = trim($request->post('code', ''));
        $appletType = AppletConfig::APPLET_FOUR;
        if (!$code) {
            return self::err('登陆参数为空');
        }
        $applet = new AppletPay($appletType);
        $auth = $applet->getAuth($code);
        if (!$auth) {
            return self::err('获取授权信息失败');
        }
        $openId = $auth->openid ?? '';
        if (!$openId) {
            return self::err('获取授权手机号失败,请重新授权');
        }
        $user = QueryUserModel::findOneByCond(['open_id' => $openId]);
        if (!$user) {
            // 不存在用户创建用户
            $user = QueryUserModel::add(['open_id' => $openId]);
        }
        $userToken = Yii::$app->getSecurity()->generateRandomString(); // 生成token
        $ret = RedisService::setKeyWithExpire(sprintf('%s%s', CommonService::USER_TOKEN_PREFIX, $userToken), $user->id, CommonService::USER_TOKEN_TIME_OUT);
        if (!$userToken || !$ret) { // 创建token失败或保存失败
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常，请联系客服',
            ]);
        }
        $tokenArr = [
            'token' => $userToken,
        ];
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '登录成功',
            'results' => [$tokenArr]
        ]);
    }
}