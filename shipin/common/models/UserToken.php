<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user_token".
 *
 * @property int $id
 * @property int $userid 用户ID
 * @property string $access_token Token
 * @property int $expiry_timestamp token过期时间
 * @property string $created_at 创建时间
 */
class UserToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userid', 'expiry_timestamp'], 'required'],
            [['userid', 'expiry_timestamp'], 'integer'],
            [['created_at'], 'safe'],
            [['access_token'], 'string', 'max' => 128],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'access_token' => 'Access Token',
            'expiry_timestamp' => 'Expiry Timestamp',
            'created_at' => 'Created At',
        ];
    }
}
