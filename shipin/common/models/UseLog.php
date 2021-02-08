<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "use_log".
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property int $type 类型 1尺寸剪裁 2时长剪裁 3加水印 4获取音频 5倒放 6修改封面 7变速 8压缩 9修改md5 10去水印
 * @property int|null $platform 平台 1-IOS 2-Android 3-Applets
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class UseLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'use_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'type', 'platform'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
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
            'type' => 'Type',
            'platform' => 'Platform',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
