<?php

namespace query\controllers;

use common\bases\CommonService;
use common\extend\check\WXBizMsgCrypt;
use common\models\MonitorLogModel;
use common\services\CurlService;
use common\models\QueryConfModel;
use DOMDocument;
use common\services\RedisService;
use query\bases\ApiController;
use common\services\WxCheckService;
use query\services\UserService;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;

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
                'actions' => ['contact', 'common', 'index'],
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
        return 'query';
    }

    /**
     * 名称检测
     * @return string
     */
    public function actionQuery()
    {
        $user = Yii::$app->user->identity;
        $request = Yii::$app->request;
        $name = trim($request->get('name', ''));
        if ($name == '') {
            return self::err('查询参数不能为空');
        }
        $availableNum = UserService::availableNum($user);
        if ($availableNum <= 0) {
            return self::err('您今日的免费次数已使用完');
        }
        $lockKeyUser = sprintf('%s_%s', RedisService::KEY_QUERY_USER, $user->id);
        try {
            $mutex = Yii::$app->mutex;
            $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
        } catch (Exception $e) {
            Yii::error('Redis服务异常：' . $e->getMessage());
            CommonService::sendDingMsg('Redis服务异常');
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
        }
        if ($lockUser) {
            $ret = WxCheckService::checkName($name);
            if ($ret['code'] !== '') {
                UserService::incQueryCnt($user);
            }
            $mutex->release($lockKeyUser); // 释放锁
            return self::successMsg($ret['msg']);
        }
        return self::err('系统繁忙，请稍后再试');
    }

    /**
     * 联系我们
     * @return string
     */
    public function actionContact()
    {
        $email = RedisService::hGet(RedisService::KEY_QUERY_CONF, 'email') ?? '';
        $wechat = RedisService::hGet(RedisService::KEY_QUERY_CONF, 'wechat') ?? '';
        $officialAccount = RedisService::hGet(RedisService::KEY_QUERY_CONF, 'official_account') ?? '';
        $data = [
            'email' => !empty($email) ? $email : '',
            'wechat' => !empty($wechat) ? $wechat : '',
            'official_account' => !empty($officialAccount) ? Yii::$app->params['img_prefix'] . $officialAccount : ''
        ];
        return self::success(['results' => [$data]]);
    }

    /**
     * 获取公共参数
     * @return string
     */
    public function actionCommon()
    {
        $freeNum = (int)RedisService::hGet(RedisService::KEY_QUERY_CONF, 'free_num');
        $data = [
            'free_num' => $freeNum,
        ];
        return self::success(['results' => [$data]]);
    }

    /**
     *获取我的剩余查询次数
     * @return string
     */
    public function actionMy()
    {
        $user = Yii::$app->user->identity;
        $availableNum = UserService::availableNum($user);
        $data = [
            'surplus_num' => $availableNum,
        ];
        return self::success(['results' => [$data]]);
    }

    /**
     * 添加监控名称
     * @return string
     */
    public function actionMonitor()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $name = trim($request->post('name', ''));
        if (strlen($name) > MonitorLogModel::LEN_LIMIT) {
            return self::err(sprintf('名称不得超过%s', MonitorLogModel::LEN_LIMIT));
        }
        if (empty($name)) {
            return self::err('名称不能为空');
        }
        $monitor = MonitorLogModel::getOne([
            'name' => $name,
            'user_id' => $userId,
        ]);
        if ($monitor) {
            return self::err('该名称已添加，请勿重复添加');
        }
        $check = WxCheckService::checkName($name);
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'code' => (string)$check['code'],
            'state' => MonitorLogModel::STATE_OPEN,
        ];
        $ret = MonitorLogModel::add($data);
        if ($ret) {
            return self::successMsg('添加成功');
        }
        return self::err('添加失败');
    }

    /**
     * 更新监控状态
     * @return string
     */
    public function actionUpdateMonitor()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $id = (int)$request->post('id', 0);
        $state = (int)$request->post('state', 0);
        if (!in_array($state, [MonitorLogModel::STATE_OPEN, MonitorLogModel::STATE_CLOSE])) {
            return self::err('状态错误');
        }
        $monitor = MonitorLogModel::getOne([
            'id' => $id,
            'user_id' => $userId,
        ]);
        if (!$monitor) {
            return self::err('查无该记录，无法操作');
        }
        $ret = MonitorLogModel::updateByCond(['id' => $id], [
            'state' => $state
        ]);
        if ($ret) {
            return self::successMsg('修改成功');
        }
        return self::err('修改失败');
    }

    /**
     * 获取监控列表
     * @return string
     */
    public function actionMonitorList()
    {
        $userId = Yii::$app->user->getId();
        $list = MonitorLogModel::getAll(['user_id' => $userId]);
        foreach ($list as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
                'msg' => WxCheckService::codeFilter($row->code),
                'date' => $row->updated_at,
                'state' => $row->state,
            ];
        }
        return self::success(['result' => $data ?? []]);
    }
}