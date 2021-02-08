<?php
/**
 * 后台控制器基类
 */

namespace backend\bases;

use backend\models\AdminModel;
use common\extend\utils\IPUtils;
use common\models\SysModel;
use Yii;
use yii\helpers\Json;
use common\bases\CommonController;

class BackendController extends CommonController
{
    const STATUS_NOLOGIN = 'NOLOGIN';

    const SYSTEM_ERR_MESSAGE = '系统繁忙，请重试';

    const ARROW_ACCESS_USER = 'xmadmin';

    public $logRemarks = '';

    /**
     * 判断是否有登录
     */
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        // 是否执行登出操作
        $temp = self::isLeave();
        if ($temp && $this->action->id != 'basic') {
            echo Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '您的账号已被强制登出，详情请咨询管理员'
            ]);
            if (Yii::$app->session->get($temp) >= 1) {
                Yii::$app->user->logout();
                Yii::$app->session->remove($temp);
            }
            return false;
        }
        if (Yii::$app->user->isGuest && $this->action->id != 'login' && $this->action->id != 'captcha') {
            if (Yii::$app->request->isAjax) {
                echo Json::encode([
                    'status' => self::STATUS_NOLOGIN,
                    'error_message' => '登录超时，请重新登陆'
                ]);
            } else {
                $this->redirect(['site/login']);
            }
            return false;
        } else {
            //判断是否有权限
            $action = Yii::$app->controller->route;
            if ($action && Yii::$app->params['is_open_rbac']) {
                $action = '/' . $action;
                $permission = Yii::$app->authManager->getPermission($action);
                if ($action == '/site/login' || $action == '/site/captcha'
                    || ($permission
                        && ((empty($permission->ruleName) && in_array($action, Yii::$app->user->identity->getRoutes()))
                            || ($permission->ruleName && Yii::$app->user->can($action))))) {
                    return true;
                } else {
                    $notice = '此操作';
                    if (Yii::$app->request->isAjax) {
                        echo Json::encode([
                            'status' => self::STATUS_FAILURE,
                            'error_message' => '对不起，您没有' . $notice . '的权限'
                        ]);
                    } else {
                        // $this->redirect(['site/error', 'msg' => '对不起，您没有' . $notice . '的权限']);
                        echo Json::encode([
                            'status' => self::STATUS_FAILURE,
                            'error_message' => '对不起，您没有' . $notice . '的权限'
                        ]);
                        exit();
                    }
                    return false;
                }
            } else {
                return false;
            }
        }

    }

    public function afterAction($action, $result)
    {
        return parent::afterAction($action, $result);
    }

    /**
     * 判断用户是否离职
     * @return bool
     */
    private function isLeave()
    {
        $user = Yii::$app->user->identity;
        if ($user && ($user->state == AdminModel::RESIGNED || $user->state == AdminModel::LOCK)) {
            $temp = sprintf('admin_leave_%s', $user->id);
            Yii::$app->session->set($temp, 1);
            return $temp;
        }
        return false;
    }
}