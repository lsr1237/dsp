<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mobile_code".
 *
 * @property integer $id ID
 * @property string $mobile 手机号码
 * @property string $code 验证码
 * @property integer $created_at 添加时间
 * @property integer $expire_time 过期时间
 */
class MobileCode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mobile_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'expire_time'], 'integer'],
            [['mobile'], 'string', 'max' => 11],
            [['code'], 'string', 'max' => 8],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => 'Mobile',
            'code' => 'Code',
            'created_at' => 'Addtime',
            'expire_time' => 'Expire Time',
        ];
    }
}
