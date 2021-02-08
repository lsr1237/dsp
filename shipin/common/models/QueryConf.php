<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "query_conf".
 *
 * @property int $id 用户ID
 * @property int $free_num 每日免费查询次数
 * @property string $email 邮箱
 * @property string $wechat 微信号
 * @property string $official_account 微信公众号图片
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class QueryConf extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'query_conf';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['free_num'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['email', 'wechat', 'official_account'], 'string', 'max' => 128],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'free_num' => 'Free Num',
            'email' => 'Email',
            'wechat' => 'Wechat',
            'official_account' => 'Official Account',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
