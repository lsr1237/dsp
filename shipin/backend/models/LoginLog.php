<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "login_log".
 *
 * @property int $id
 * @property int $admin_id 管理员ID
 * @property string $login_ip 登入IP
 * @property string $login_time 登入时间
 */
class LoginLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'login_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['admin_id'], 'integer'],
            [['login_time'], 'safe'],
            [['login_ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'admin_id' => 'Admin ID',
            'login_ip' => 'Login Ip',
            'login_time' => 'Login Time',
        ];
    }

    public function getAdmin()
    {
        return $this->hasOne(Admin::className(), ['id' => 'admin_id']);
    }
}
