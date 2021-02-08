<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mobile_log".
 *
 * @property int $id
 * @property string|null $mobile 电话号码
 * @property string $type 类型 auth_code:短信验证码
 * @property string|null $return_message 返回信息
 * @property string|null $send_message 发送内容
 * @property string|null $content 短信内容
 * @property string $created_at 创建时间
 */
class MobileLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mobile_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at'], 'safe'],
            [['mobile'], 'string', 'max' => 11],
            [['type'], 'string', 'max' => 13],
            [['return_message', 'send_message'], 'string', 'max' => 255],
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
            'mobile' => 'Mobile',
            'type' => 'Type',
            'return_message' => 'Return Message',
            'send_message' => 'Send Message',
            'content' => 'Content',
            'pay_id' => 'Pay ID',
            'created_at' => 'Created At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['mobile' => 'mobile']);
    }
}
