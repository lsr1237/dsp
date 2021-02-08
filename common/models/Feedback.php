<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "feedback".
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property string|null $contact_info 联系方式
 * @property string|null $content 反馈内容
 * @property int|null $platform 平台 1-IOS 2-Android 3-Applets
 * @property int $state 状态 1-未处理 2-已处理
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Feedback extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'platform', 'state'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['contact_info'], 'string', 'max' => 15],
            [['content'], 'string', 'max' => 300],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'contact_info' => 'Contact Info',
            'content' => 'Content',
            'platform' => 'Platform',
            'state' => 'State',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
