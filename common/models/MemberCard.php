<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "member_card".
 *
 * @property int $id
 * @property int $wx_id 小程序id
 * @property string $name 会员卡名称
 * @property float $ori_price 原价（元）
 * @property float $cur_price 现价（元）
 * @property int $term 期限（天）
 * @property int $num 使用次数
 * @property int $sort 排序，数值越小越靠前
 * @property int $state 状态 1:显示 2:隐藏
 * @property int $type 类型 1:vip会员 2:次数会员
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class MemberCard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'member_card';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['wx_id', 'term', 'num', 'sort', 'state', 'type'], 'integer'],
            [['ori_price', 'cur_price'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'wx_id' => 'Wx ID',
            'name' => 'Name',
            'ori_price' => 'Ori Price',
            'cur_price' => 'Cur Price',
            'term' => 'Term',
            'num' => 'Num',
            'sort' => 'Sort',
            'state' => 'State',
            'type' => 'Type',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
