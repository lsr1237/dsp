<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "monitor_log".
 *
 * @property int $id id
 * @property int $user_id 用户id
 * @property string $name 监控名称
 * @property string $code 状态码
 * @property int $state 状态 1-开启 2-关闭
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class MonitorLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'monitor_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'state'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['code'], 'string', 'max' => 10],
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
            'name' => 'Name',
            'code' => 'Code',
            'state' => 'State',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(QueryUser::class, ['id' => 'user_id']);
    }
}
