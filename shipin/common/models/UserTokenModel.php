<?php

namespace common\models;

use common\bases\CommonModel;

class UserTokenModel extends CommonModel
{

    // Access Token的过期时间30天的秒数
    const TOKEN_EXPIRY = 2592000;

    /**
     * 生成token
     * @param integer $userId 用户ID
     * @return object|boolean|UserToken
     * @throws \yii\base\Exception
     */
    public static function generateUserToken($userId)
    {
        $userToken = self::findValidToken($userId);
        if ($userToken) {
            return $userToken;
        }
        $time = time();
        $userToken = new UserToken();
        $userToken->userid = $userId;
        $userToken->expiry_timestamp = $time + self::TOKEN_EXPIRY;
        // 生成Token
        $userToken->access_token = \Yii::$app->getSecurity()->generateRandomString();
        if ($userToken->save()) {
            return $userToken;
        } else {
            return false;
        }
    }

    /**
     * 生成token
     * @param string $accessToken 用户AccessToken
     * @return object|boolean [[UserToken]]
     */
    public static function refreshUserToken($accessToken)
    {
        $userToken = self::getUserTokenByAccessToken($accessToken);
        $time = time();
        $userToken->expiry_timestamp = $time + self::TOKEN_EXPIRY;
        if ($userToken->save()) {
            return $userToken;
        } else {
            return false;
        }
    }

    /**
     * 销毁token（设置为过期）
     * @param object|string $accessToken 用户AccessToken
     * @return object|boolean [[UserToken]]
     */
    public static function dropUserToken($accessToken)
    {
        $userToken = self::getUserTokenByAccessToken($accessToken);
        if (!$userToken) {
            return false;
        }
        $time = time();
        $userToken->expiry_timestamp = $time - 1;
        if ($userToken->save()) {
            return $userToken;
        } else {
            return false;
        }
    }

    /**
     * 根据 access token 获取UserToken
     * @param integer $accessToken 用户AccessToken
     * @return object|false|UserToken
     */
    public static function getUserTokenByAccessToken($accessToken)
    {
        $userToken = UserToken::findOne(['access_token' => $accessToken]);
        return $userToken ? $userToken : false;
    }

    /**
     * 校验Access Token
     * @param string $accessToken 用户Access Token
     * @return object|boolean|UserToken
     */
    public static function validateAccessToken($accessToken)
    {
        if (!$accessToken) {
            return false;
        }
        $userToken = self::getUserTokenByAccessToken($accessToken);
        $nowTime = time();
        if ($userToken && $nowTime <= $userToken->expiry_timestamp) {
            return $userToken;
        } else {
            return false;
        }
    }

    /**
     * 设置用户的所有token过期
     * @param $userId
     * @return int
     */
    public static function destroyUserTokenByUserId($userId)
    {
        return UserToken::updateAll(['expiry_timestamp' => (time() - 1)], ['userid' => $userId]);
    }

    /**
     * 查找有效token
     * @param $userId
     * @return array|null|\yii\db\ActiveRecord|UserToken
     */
    public static function findValidToken($userId)
    {
        return UserToken::find()->where(['userid' => $userId])
            ->andWhere(['>', 'expiry_timestamp', time()])
            ->one();
    }
}
