<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "err_log".
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property int $log_id 记录ID
 * @property string $host 域名
 * @property string $url 链接
 * @property string $msg 错误信息
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class ErrLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'err_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'log_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['url'], 'string', 'max' => 1200],
            [['host'], 'string', 'max' => 255],
            [['msg'], 'string', 'max' => 300],
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
            'log_id' => 'Log ID',
            'host' => 'Host',
            'url' => 'Url',
            'msg' => 'Msg',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
