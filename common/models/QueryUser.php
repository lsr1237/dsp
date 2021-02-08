<?php

namespace common\models;

use common\bases\CommonService;
use common\services\RedisService;
use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "query_user".
 *
 * @property int $id 用户ID
 * @property string $open_id openid
 * @property string $created_at 注册时间
 * @property string $updated_at 更新时间
 */
class QueryUser extends ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'query_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'safe'],
            [['open_id'], 'string', 'max' => 64],
            [['open_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($userId)
    {
        return static::findOne(['id' => $userId]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $key = sprintf('%s%s', CommonService::USER_TOKEN_PREFIX, $token);
        $userId = RedisService::getKey($key);
        if (!$userId) {
            return null;
        }
        $user = self::findIdentity($userId);
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

}
