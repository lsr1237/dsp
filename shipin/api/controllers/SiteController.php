<?php

namespace api\controllers;

use api\bases\ApiController;
use common\bases\CommonService;
use common\extend\Tool;
use common\extend\wx\AppletConfig;
use common\extend\wx\AppletPay;
use common\models\ErrLogModel;
use common\models\FeedbackModel;
use common\models\HelpInfoModel;
use common\models\MemberCardModel;
use common\models\MobileCodeModel;
use common\models\MobileLogModel;
use common\models\NoticeModel;
use common\models\UseLogModel;
use common\models\User;
use common\models\UserModel;
use common\models\UserTokenModel;
use common\models\VideoLogModel;
use common\services\JjSmsService;
use common\services\RedisService;
use common\services\UserService;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use yii\db\Exception as DBException;
use yii\base\Exception as BaseException;
use yii\db\IntegrityException;

/**
 * Site controller
 */
class SiteController extends ApiController
{

    const SEND_CODE = 'code'; // 验证码
    const WAIT_TIME = 60; // 请求发送验证码随机值redis存活时间60s
    const LOGIN_TYPE_PASSWORD = 'pwd'; // 账号密码登陆
    const LOGIN_TYPE_SMS = 'sms'; // 账号短信验证码登陆
    const SMS_CODE_TYPE_REGISTER = 'register'; // 类型-注册
    const SMS_CODE_TYPE_LOGIN = 'login'; // 类型-登陆
    const SMS_CODE_TYPE_RESET_PWD = 'reset_pwd'; // 类型-忘记密码

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => [
                    'login',
                    'error',
                    'index',
                    'signup',
                    'rt',
                    'send-mobile-code',
                    'vip-list',
                    'home',
                    'forget-pwd',
                    'contact',
                    'help',
                    'help-info',
                ],
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
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
//            'error' => [
//                'class' => 'yii\web\ErrorAction',
//            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionError()
    {
        $response = Yii::$app->response;
        $code = $response->getStatusCode();
        $response->setStatusCode(200);
        if ($code == 500) {
            return self::err('500:系统错误');
        } elseif ($code == 404) {
            return self::err('404');
        };
        return self::err($code);
    }

    public function actionIndex()
    {
//        $request = Yii::$app->request;
//        $code = $request->get('code', '');
//        $applet = new AppletPay('11');
//        $auth = $applet->getAuth($code, true);
//        if (!$auth) {
//            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '微信授权信息获取失败12']);
//        }
//        Yii::error($code, 'wx');
//        Yii::error($auth->openid, 'wx');
//        die();
//        $outTradeNo = Tool::getOrderNo(1);
//        $wxRet = $applet->unifiedOrder('测试', $outTradeNo, 0.01 * 100, $auth->openid);
//        Yii::error(json_encode($wxRet), 'wx');
//        var_dump($wxRet);
//        return $code;
        return 'sp';
    }

    /**
     * 注册
     * @return string
     */
    public function actionSignup()
    {
        try {
            $request = Yii::$app->request;
            $code = $request->post('code', '');
            $mobile = trim($request->post('mobile', ''));
            $password = trim($request->post('password', ''));
            if (empty($mobile)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入手机号']);
            }
            // 验证重复注册
            if (UserModel::getUserByMobile($mobile)) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '该手机号码已注册',
                ]);
            }
            if (empty($password)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入密码']);
            }
            if ($code == '') {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入验证码']);
            }
            $checkCode = MobileCodeModel::checkMobileCode($mobile, $code); // 短信验证码
            if (!$checkCode) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '验证码不正确或已过期',
                ]);
            }
            $transaction = Yii::$app->db->beginTransaction();
            $user = UserModel::add([
                'mobile' => $mobile,
                'password' => Yii::$app->security->generatePasswordHash($password),
                'state' => User::STATE_ACTIVE,
                'number' => Tool::getRandomString(9, 'abcdefghijklmnopqrstuvwxyz0123456789')
            ]);

            if (!$user) { // DB保存用户失败
                $transaction->rollBack();
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '系统异常, 请联系客服',
                ]);
            }
            $transaction->commit();
            return Json::encode([
                'status' => self::STATUS_SUCCESS,
                'error_message' => '注册成功',
                // 'results' => [$tokenArr]
            ]);
        } catch (IntegrityException $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常, 请联系客服',
            ]);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常, 请联系客服',
            ]);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常, 请联系客服',
            ]);
        }
    }

    /**
     * 登录
     * @return string
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;
        $mobile = trim($request->post('mobile'));
        $type = trim($request->post('type', ''));
        if (!in_array($type, [self::LOGIN_TYPE_PASSWORD, self::LOGIN_TYPE_SMS])) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '登陆类型错误',
            ]);
        }
        try {
            $user = UserModel::getUserByMobile($mobile);
            if (!$user) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '您的手机号码尚未注册，请先完成注册',
                ]);
            }
            if ($type == self::LOGIN_TYPE_PASSWORD) {
                if (!$user->password) {
                    return self::err('您还未设置登陆密码, 请使用短信登陆');
                }
                $password = trim($request->post('password'));
                if ($password == '') {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入密码']);
                }
                if (!$user || !UserModel::validatePassword($password, $user)) {
                    return Json::encode([
                        'status' => self::STATUS_FAILURE,
                        'error_message' => '手机号或密码错误',
                    ]);
                }
            } elseif ($type == self::LOGIN_TYPE_SMS) {
                $code = trim($request->post('code', ''));
                if ($code == '') {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入验证码']);
                }
                $checkCode = MobileCodeModel::checkMobileCode($mobile, $code); // 短信验证码
                if (!$checkCode) {
                    return Json::encode([
                        'status' => self::STATUS_FAILURE,
                        'error_message' => '验证码不正确或已过期',
                    ]);
                }
            }
            if ($user->state == User::STATE_INACTIVE) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '您的账号已被禁止使用，详情请联系客服',
                ]);
            }

            // $userToken = Yii::$app->getSecurity()->generateRandomString(); // 生成token
            // $ret = RedisService::setKeyWithExpire(sprintf('%s%s', CommonService::USER_TOKEN_PREFIX, $userToken), $user->id, CommonService::USER_TOKEN_TIME_OUT);
            $userToken = UserTokenModel::generateUserToken($user->id);
            if (!$userToken) { // 创建token失败或保存失败
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '系统异常，请联系客服',
                ]);
            }
            $isMember = UserService::isMember($user); // 判断用户会员是否过期
            $tokenArr = [
                'mobile' => $mobile,
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
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常，请联系客服',
            ]);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '系统异常，请联系客服',
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        $headers = Yii::$app->request->headers;
        $user = Yii::$app->user->identity;
        if ($user) {
            Yii::$app->user->logout();
        }
        $accessToken = trim($headers->get('Authorization'));
        //$droppedUserToken = RedisService::delKey(sprintf('%s%s', CommonService::USER_TOKEN_PREFIX, $accessToken));  // 销毁token
        $droppedUserToken = UserTokenModel::dropUserToken($accessToken);
        if (!$droppedUserToken) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => ''
            ]);
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '退出成功'
        ]);
    }

    /**
     * 忘记密码
     * @return string
     */
    public function actionForgetPwd()
    {
        $request = Yii::$app->request;
        $password = trim($request->post('password', ''));
        $mobile = trim($request->post('mobile', ''));
        $code = trim($request->post('code', ''));
        if ($mobile == '') {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请填写手机号码']);
        }
        if ($code == '') {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请填写验证码']);
        }
        if (!$password) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请填写新密码']);
        }
        $checkCode = MobileCodeModel::checkMobileCode($mobile, $code); // 短信验证码
        if (!$checkCode) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '验证码不正确或已过期（有效期为15分钟）']);
        }
        try {
            $user = UserModel::updatePasswordByMobile($mobile, $password);
            if (!$user) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '手机号未注册, 请先注册']);
            }
        } catch (DBException $e) {
            Yii::error($e);
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统故障, 密码重置失败, 请重试']);
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '密码重置成功']);
    }

    /**
     * 获取验证码随机值
     * @return string
     */
    public function actionRt()
    {
        $request = Yii::$app->request;
        $mobile = trim($request->get('mobile'));
        if (empty($mobile) or !preg_match("/^(1[0-9]{10})+$/", $mobile)) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '请输入可用的手机号码'
            ]);
        }
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $randString = Tool::getRandomString(31, $chars);
        $randNum = Tool::getRandomNum(1);
        $randValue = sprintf('%s%s', $randString, $randNum);
        RedisService::setKeyWithExpire(sprintf('%s_%s', self::SEND_CODE, $mobile), $randValue, self::WAIT_TIME);
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => ['rt' => $randValue ?? '']
        ]);
    }

    /**
     * 发送验证码
     * @return string
     */
    public function actionSendMobileCode()
    {
        $request = Yii::$app->request;
        $mobile = trim($request->post('mobile'));
        $rt = trim($request->post('rt', ''));
        $type = trim($request->post('type', self::SMS_CODE_TYPE_REGISTER));
        if (!in_array($type, [self::SMS_CODE_TYPE_LOGIN, self::SMS_CODE_TYPE_RESET_PWD, self::SMS_CODE_TYPE_REGISTER])) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '类型错误'
            ]);
        }
        if (!$rt || strlen($rt) != 32) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '参数错误'
            ]);
        }
        if (empty($mobile) or !preg_match("/^(1[0-9]{10})+$/", $mobile)) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '请输入可用的手机号码'
            ]);
        }
        $randValue = RedisService::getKey(sprintf('%s_%s', self::SEND_CODE, $mobile));
        if (($randValue === false) || ($randValue == $rt)) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '参数错误'
            ]);
        }
        if ($rt[$rt[strlen($rt) - 1]] != $randValue[$randValue[strlen($randValue) - 1]]) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '验证不通过'
            ]);
        }
        $user = UserModel::getUserByMobile($mobile);
        if ($type == self::SMS_CODE_TYPE_REGISTER && $user) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '您已经注册过了',
                'error_type' => self::ERROR_TYPE_REGISTERED,
            ]);
        }
        if (in_array($type, [self::SMS_CODE_TYPE_LOGIN, self::SMS_CODE_TYPE_RESET_PWD]) && !$user) {

            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '您尚未注册，请先完成注册',
                'error_type' => self::ERROR_TYPE_REGISTERED,
            ]);
        }
        $code = Tool::getRandomNum(6); // 短信验证码
        $minutes = MobileCodeModel::EFFECTIVE_TIME;
        return CommonService::sendMobileSms($mobile, JjSmsService::SMS_MAP[self::SMS_CODE_TYPE_LOGIN], [$code, $minutes], MobileLogModel::TYPE_AUTHENTICATION_CODE);
    }

    /**
     * 修改密码
     * @return string
     */
    public function actionPassword()
    {
        $currentUser = Yii::$app->user->identity;
        $request = Yii::$app->request;
        $oldPwd = trim($request->post('old_password', '')); // 当前密码
        $newPwd = trim($request->post('new_password', '')); // 新密码
        $rePwd = trim($request->post('repeat_password', '')); // 重复密码
        if (!$newPwd || !$rePwd) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请填写当前密码,新密码,确认密码']);
        }
        if (mb_strlen($newPwd) < 6 || mb_strlen($newPwd) > 15) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请使用6~15位数字、字母、特殊符号组合']);
        }
        if ($newPwd != $rePwd) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '新密码与确认密码不一致']);
        }
        if ($currentUser->password) {
            if (!$oldPwd) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请填写当前密码']);
            }
        }
        if ($currentUser->password && !UserModel::validatePassword($oldPwd, $currentUser)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '当前密码错误，请重试']);
        }
        $result = UserModel::updateByCond(['id' => $currentUser->id], [
            'password' => Yii::$app->security->generatePasswordHash($rePwd)
        ]); // 修改密码
        if ($result) {
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '密码修改成功']);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '修改失败，请重试']);
    }

    /**
     * 会员卡列表
     * @return string
     */
    public function actionVipList()
    {
        $request = Yii::$app->request;
        $appletType = (int)$request->get('applet_type', AppletConfig::APPLET_ONE); // 小程序id
        if (in_array($appletType, AppletConfig::OFFICIAL_ARR)) {
            $appletType = array_search($appletType, AppletConfig::OFFICIAL_APPLET) ?? 0;
        }
        $key = sprintf('%s_%s', RedisService::KEY_MEMBER_CARDS, $appletType);
        $cards = RedisService::getKey($key);
        if (!$cards) {
            $cards = [];
            $cardsList1 = MemberCardModel::getActiveCardsByTypeAndWxId(MemberCardModel::TYPE_VIP, $appletType);
            $cardsList2 = MemberCardModel::getActiveCardsByTypeAndWxId(MemberCardModel::TYPE_NUM, $appletType);
            foreach ($cardsList1 as $list1) {
                $cards['vip'][] = [
                    'id' => $list1->id,
                    'name' => $list1->name,
                    'ori_price' => floatval($list1->ori_price),
                    'cur_price' => floatval($list1->cur_price),
                    'term' => $list1->term,
                ];
            }
            foreach ($cardsList2 as $list2) {
                $cards['num'][] = [
                    'id' => $list2->id,
                    'name' => $list2->name,
                    'ori_price' => floatval($list2->ori_price),
                    'cur_price' => floatval($list2->cur_price),
                    'num' => $list2->num,
                ];
            }
            RedisService::setKey($key, Json::encode($cards));
        } else {
            $cards = Json::decode($cards);
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => [$cards]
        ]);
    }

    /**
     * 首页
     * @return string
     */
    public function actionHome()
    {
        $user = Yii::$app->user->identity ?? '';
        $request = Yii::$app->request;
        $appletType = (int)$request->get('applet_type', 0); // 小程序id
        $key = sprintf('%s_%s', RedisService::KEY_APP_BASIC, $appletType);
        $shareTitle = RedisService::hGet($key, 'share_title');
        $tip = RedisService::hGet($key, 'tip');
        $button = RedisService::hGet($key, 'button');
        $link = RedisService::hGet($key, 'link');
        $rewardNum = RedisService::hGet($key, 'reward_num');
        $auditState = (int)RedisService::hGet($key, 'audit_state');
        $key = sprintf('%s_%s', RedisService::KEY_BANNER, $appletType);
        $banner = RedisService::getKey($key);
        if (!$banner) {
            $banner = [];
            $banners = NoticeModel::getNoticeByCond([
                'wx_id' => $appletType,
                'type' => NoticeModel::TYPE_BANNER,
                'state' => NoticeModel::STATE_SHOW
            ]);
            if ($banners) {
                foreach ($banners as $b) {
                    array_push($banner, [
                        'img' => Yii::$app->params['img_prefix'] . $b->image,
                        'url' =>  $b->url,
                        'is_jump' => $b->is_jump,
                        'app_id' => $b->app_id,
                    ]);
                }
                RedisService::setKeyWithExpire($key, Json::encode($banner), RedisService::EXPIRE);
            }
        } else {
            $banner = Json::decode($banner);
        }
        $ad = RedisService::getKey(sprintf('%s_%s', RedisService::KEY_AD_IDS, $appletType));
        if ($ad) {
            $ad = json_decode($ad, true);
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => [
                [
                    'banner' => $banner,
                    'is_member' => UserService::isMember($user),
                    'num' => UserService::userNum($user),
                    'share_title' => !empty($shareTitle) ? $shareTitle : '',
                    'tip' => !empty($tip) ? $tip : '',
                    'button' => !empty($button) ? $button : '',
                    'link' => !empty($link) ? $link : '',
                    'reward_num' => !empty($rewardNum) ? $rewardNum : '',
                    'audit_state' => $auditState,
                    'ad' => !empty($ad) ? $ad : null
                ]
            ]
        ]);
    }

    /**
     * 上报产品使用情况
     * @return string
     */
    public function actionReport()
    {
        $request = Yii::$app->request;
        $user = Yii::$app->user->identity;
        $type = (int)$request->post('type', 0);
        $platform = (int)$request->post('platform', 0);
        if (!in_array($type, UseLogModel::TYPE_MAP)) {
            return self::err('上报类型错误');
        }
        $data = [
            'user_id' => $user->id,
            'type' => $type,
            'platform' => $platform,
        ];
        $lockKeyUser = sprintf('%s_%s', self::KEY_USER, $user->id);
        try {
            $mutex = Yii::$app->mutex;
            $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
        } catch (Exception $e) {
            Yii::error('Redis服务异常：' . $e->getMessage());
            CommonService::sendDingMsg('Redis服务异常');
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
        }
        if ($lockUser) {
            UserService::isExec($user);
            $ret = UseLogModel::add($data);
            if ($ret) {
                $mutex->release($lockKeyUser); // 释放锁
                return self::success([
                    'error_message' => '上报成功',
                    'results' => [],
                ]);
            }
            $mutex->release($lockKeyUser); // 释放锁
            return self::err('上报失败');
        }
        return self::err('系统繁忙请稍后再试');
    }

    /**
     * 反馈
     * @return string
     */
    public function actionFeedback()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $content = trim($request->post('content', ''));
        $contactInfo = trim($request->post('contact_info', ''));
        $platform = trim($request->post('platform', ''));
        if (empty($content)) {
            return self::err('内容不能为空');
        }
        if (mb_strlen($content) >= FeedbackModel::CONTENT_LIMIT) {
            return self::err(sprintf('内容不得超过%s个字', FeedbackModel::CONTENT_LIMIT));
        }
        if (mb_strlen($contactInfo) >= FeedbackModel::INFO_LIMIT) {
            return self::err(sprintf('联系方式不得超过%s个字', FeedbackModel::INFO_LIMIT));
        }
        if (!in_array($platform, [
            FeedbackModel::PLATFORM_IOS,
            FeedbackModel::PLATFORM_ANDROID,
            FeedbackModel::PLATFORM_APPLETS,
        ])) {
            return self::err('平台参数错误');
        }
        $data = [
            'user_id' => $userId,
            'content' => $content,
            'contact_info' => $contactInfo,
            'platform' => $platform,
            'state' => FeedbackModel::STATE_UNDO
        ];
        $ret = FeedbackModel::add($data);
        if ($ret) {
            return self::successMsg('反馈成功');
        }
        return self::err('反馈失败');
    }

    /**
     * 联系客服
     * @return string
     */
    public function actionContact()
    {
        $request = Yii::$app->request;
        $appletType = (int)$request->get('applet_type', 0); // 小程序id
        $key = sprintf('%s_%s', RedisService::KEY_APP_BASIC, $appletType);
        $wechat = RedisService::hGet($key, 'wechat') ?? '';
        $subscription = RedisService::hGet($key, 'subscription') ?? '';
        $data = [
            'wechat' => !empty($wechat) ? $wechat : '',
            'subscription' => !empty($subscription) ? Yii::$app->params['img_prefix'] . $subscription : '',
        ];
        return self::success(['results' => [$data]]);
    }

    public function actionHelp()
    {
        $request = Yii::$app->request;
        $appletType = (int)$request->get('applet_type', 0); // 小程序id
        $help = [];
        $helps = HelpInfoModel::getAllByCond(['wx_id' => $appletType, 'state' => HelpInfoModel::STATE_SHOW]);
        if ($helps) {
            foreach ($helps as $h) {
                array_push($help, [
                    'question' => $h->question,
                    'answer' => $h->answer,
                ]);
            }
        }
//        $key = sprintf('%s_%s', RedisService::KEY_HELP, $appletType);
//        $help = RedisService::getKey($key);
//        if (!$help) {
//            $help = [];
//            $helps = HelpInfoModel::getAllByCond(['wx_id' => $appletType, 'state' => HelpInfoModel::STATE_SHOW]);
//            if ($helps) {
//                foreach ($helps as $h) {
//                    array_push($help, [
//                        'question' => $h->question,
//                        'answer' => $h->answer,
//                    ]);
//                }
//                RedisService::setKeyWithExpire($key, Json::encode($help), RedisService::EXPIRE);
//            }
//        } else {
//            $help = Json::decode($help);
//        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => $help
        ]);
    }

    /**
     * 问题中心分页面
     * @return string
     */
    public function actionHelpInfo()
    {
        $request = Yii::$app->request;
        $appletType = (int)$request->get('applet_type', 0); // 小程序id
        $type = (int)$request->get('type', 0); // 页面类型
        $key = sprintf('%s_%s', RedisService::KEY_HELP, $appletType);
        $help = RedisService::getKey($key);
        if (!$help) {
            $help = [];
            $helps = HelpInfoModel::getAllByCond(['wx_id' => $appletType, 'state' => HelpInfoModel::STATE_SHOW]);
            if ($helps) {
                foreach ($helps as $h) {
                    $help[$h->type][] = [
                        'question' => $h->question,
                        'answer' => $h->answer,
                    ];
                }
                RedisService::setKeyWithExpire($key, Json::encode($help), RedisService::EXPIRE);
            }
        } else {
            $help = Json::decode($help);
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => isset($help[$type]) ? $help[$type] : []
        ]);
    }

    /**
     * 绑定公众号openid
     * @return string
     */
    public function actionBindOfficial()
    {
        $user = Yii::$app->user->identity;
        if (!empty($user->official_openid)) {
            return self::successMsg('授权成功');
        }
        $request = Yii::$app->request;
        $code = trim($request->post('code', ''));
        $userOfficialId = AppletConfig::OFFICIAL_APPLET[$user->wx_id] ?? 0;
        if (empty($userOfficialId)) {
            return self::err('授权公众号与小程序未关联');
        }
        $applet = new AppletPay($userOfficialId);
        $auth = $applet->getAuth($code, true);
        if (!$auth) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '微信授权信息获取失败']);
        }
        $ret = UserModel::updateById($user->id,
            [
                'official_openid' => $auth->openid ?? ''
            ]
        );
        if ($ret) {
            return self::successMsg('授权成功');
        }
        return self::err('授权失败');
    }

    /**
     * 下载视频错误上报
     * @return string
     */
    public function actionSaveErr()
    {

        $request = Yii::$app->request;
        $id = trim($request->post('id', ''));
        $url = trim($request->post('url', ''));
        $msg = trim($request->post('msg', ''));
        if (!$id) {
            return self::err('id不能为空');
        }
        if (!$url) {
            return self::err('url不能为空');
        }
        if (!$msg) {
            return self::err('msg不能为空');
        }
        $user = Yii::$app->user->identity;
//        $videoLog = VideoLogModel::findOneByCond(['id' => $id, 'user_id' => $user->id]);
//        if (!$videoLog) {
//            return self::err('记录不存在');
//        }
        $urlArr = parse_url($url);
        $ret = ErrLogModel::add([
            'user_id' => $user->id,
            'log_id' => $id,
            'host' => $urlArr['host'] ?? '',
            'url' => $url,
            'msg' => $msg
        ]);
        if ($ret) {
            return self::successMsg('上报成功');
        }
        return self::err('上报失败');
    }
}
