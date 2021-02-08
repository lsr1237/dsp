<?php

namespace api\bases;

use common\bases\CommonService;
use Yii;
use yii\base\ErrorException;
use yii\filters\AccessControl;
use yii\helpers\Json;
use common\bases\CommonController;
use common\extend\filters\TokenAuth;
use common\models\User;

/**
 * API控制器基类
 */
class ApiController extends CommonController
{
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILURE = 'FAILURE';

    const ERROR_TYPE_UNAUTHORIZED = 'UNAUTHORIZED'; // 错误类型：未认证
    const ERROR_TYPE_REGISTERED = 'REGISTERED'; // 错误类型：已注册
    const ERROR_TYPE_BLACK = 'BLACK'; // 错误类型：黑名单

    const SYSTEM_ERROR_MESSAGE = '系统繁忙，请联系客服';

    const KEY_USER = 'user'; // 用户锁

    /**
     * 绑定访问控制过滤器
     *
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // Token登录
        $behaviors['tokenAuth'] = [
            'class' => TokenAuth::className(),
        ];
        // 访问控制
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'denyCallback' => function ($rule, $action) {
                echo Json::encode([
                    'status' => 'TOKEN_ERROR',
                    'error_message' => '您还没有登录，登录后才能使用该功能',
                ]);
                exit();
            },
        ];
        return $behaviors;
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        parent::beforeAction($action);

        $needRedisServerAction = [
        ]; // 需Redis服务的操作
        if (in_array(sprintf('%s/%s', Yii::$app->controller->id, $this->action->id), $needRedisServerAction)) {
            try {
                Yii::$app->redis->ping();
            } catch (ErrorException $e) {
               CommonService::sendDingMsg('redis服务异常');
                echo Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '服务器错误，请稍后再试',
                ]);
                return false;
            }
        }
        return true;
    }

    public function getUserInfo()
    {
        if (Yii::$app->user->isGuest) {
            return false;
        } else {
            return User::findOne(Yii::$app->user->identity->id);
        }
    }

    public function getUserId($mustBe = false, $returnString = 0)
    {
        $model = $this->getUserInfo();
        if (!empty($model)) {
            return $model->id;
        } else {
            if ($mustBe) {
                return Json::encode([
                    'status' => 'FAILURE',
                    'error_message' => '获取用户信息失败',
                ]);
            } else {
                return $returnString;
            }
        }
    }
}
