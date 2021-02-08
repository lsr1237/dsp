<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "num_log".
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property int $num 次数
 * @property int $type 类型 1邀请新人 2购买次数 3观看广告
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class NumLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'num_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'num', 'type'], 'integer'],
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
            'num' => 'Num',
            'type' => 'Type',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
