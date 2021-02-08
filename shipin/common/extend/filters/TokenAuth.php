<?php

namespace common\extend\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\User;
use yii\web\Request;
use yii\helpers\Json;

/**
 * Token登录授权过滤器
 */
class TokenAuth extends ActionFilter
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $user = Yii::$app->getUser();
        $request = Yii::$app->getRequest();
        // use HTTP Basic Auth User
        $token = $request->getAuthUser();
        if (!$token) {
            $headers = Yii::$app->request->headers; 
            $token = $headers->get('Authorization');
        }
        if ($token) {
            $user->identity = $user->loginByAccessToken($token);
        }
        return true;
    }

}
