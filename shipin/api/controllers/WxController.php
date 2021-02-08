<?php

namespace api\controllers;


use api\bases\ApiController;
use common\bases\CommonService;
use common\extend\Tool;
use common\extend\wx\AppletConfig;
use common\extend\wx\AppletPay;
use common\models\NumLogModel;
use common\models\UserModel;
use common\models\UserTokenModel;
use common\services\RedisService;
use common\services\UserService;
use Yii;
use yii\helpers\Json;

class WxController extends ApiController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => ['auth-login'],
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
     * 微信登陆-无手机号
     * @return string
     */
    public function actionAuthLogin()
    {
        $request = Yii::$app->request;
        $code = trim($request->post('code', ''));
        $shareCode = trim($request->post('share_code', ''));
        $appletType = (int)$request->post('applet_type', AppletConfig::APPLET_ONE);
        if (!in_array($appletType, AppletConfig::APPLET_ARR)
            && !in_array($appletType, AppletConfig::OFFICIAL_ARR)) {
            return self::err('登录类型错误');
        }
        if (!$code) {
            return self::err('登陆参数为空');
        }
        $applet = new AppletPay($appletType);
        if (in_array($appletType, AppletConfig::APPLET_ARR)) {
            $auth = $applet->getAuth($code);
        } else {
            $auth = $applet->getAuth($code, true);
        }
        if (!$auth || !isset($auth->openid)) {
            return self::err('获取授权信息失败');
        }
        $openId = $auth->openid ?? '';
        if (in_array($appletType, AppletConfig::OFFICIAL_ARR)) {
            $union = $applet->getUnionId($auth->access_token ?? '', $openId);
            if (!$union || !isset($union->unionid)) {
                return self::err('获取授权信息失败，请稍后重试');
            }
            $unionId = $union->unionid ?? '';
        } else {
            $unionId = $auth->unionid ?? '';
        }
        if (in_array($appletType, AppletConfig::APPLET_ARR)) {
            $user = UserModel::findOneByCond(['openid' => $openId]);
        } else {
            $user = UserModel::findOneByCond(['union_id' => $unionId]);
            if (!$user) {
                return self::err('用户不存在，请前往小程序注册登录');
            }
        }
        if (!$user) {
            $inviteUser = UserModel::findOneByCond(['number' => $shareCode, 'wx_id' => $appletType]);
            // 不存在用户创建用户
            $freeNum = (int)RedisService::hGet(sprintf('%s_%s', RedisService::KEY_APP_BASIC, $appletType), 'free_num');
            $user = UserModel::add([
                'openid' => $openId,
                'union_id' => $unionId,
                'wx_id' => $appletType,
                'state' => UserModel::STATE_ACTIVE,
                'number' => Tool::getRandomString(9, 'abcdefghijklmnopqrstuvwxyz0123456789'),
                'p_id' => $inviteUser->id ?? 0,
                'num' => $freeNum ?? 0
            ]);
            NumLogModel::add(['user_id' => $user->id, 'num' => $freeNum, 'type' => NumLogModel::TYPE_REGISTER]);
            if ($shareCode && $inviteUser) {
                UserService::inviteReward($inviteUser);
            }
        } else {
            if ($user->state == UserModel::STATE_INACTIVE) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '您的账号已被禁止使用，详情请联系客服',
                ]);
            }
        }
//        $userToken = Yii::$app->getSecurity()->generateRandomString(); // 生成token
//        $ret = RedisService::setKeyWithExpire(sprintf('%s%s', CommonService::USER_TOKEN_PREFIX, $userToken), $user->id, CommonService::USER_TOKEN_TIME_OUT);
        if (empty($user->union_id)) {
            UserModel::updateById($user->id, ['union_id' => $unionId]);
        }
        if (in_array($appletType, AppletConfig::OFFICIAL_ARR) && empty($user->official_openid)) {
            UserModel::updateById($user->id, ['official_openid' => $openId]);
        }
        $userToken = UserTokenModel::generateUserToken($user->id);
        if (!$userToken) { // 创建token失败或保存失败
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常，请联系客服',
            ]);
        }
        $isMember = UserService::isMember($user); // 判断用户会员是否过期
        $tokenArr = [
            'mobile' => '',
            'token' => $userToken->access_token,
            'is_member' => $isMember,
            'ent_at' => $isMember ? $user->end_at : '',
            'num' => UserService::userNum($user)
        ];
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '登录成功',
            'results' => [$tokenArr]
        ]);
    }
}