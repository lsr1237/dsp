<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "pay_log".
 *
 * @property int $id ID
 * @property int $user_id 用户ID
 * @property string $title 标题
 * @property float $amount 支付金额
 * @property string $out_trade_no 商户网站唯一订单号	
 * @property string $trade_no 支付系统中的交易流水号
 * @property int $state 状态 0-等待支付 1-支付成功 2-支付失败
 * @property int $type 类型 1-购买会员
 * @property int $pay_way 支付类型 1-支付宝 2-微信支付
 * @property string|null $end_at 交易结束时间
 * @property int $refund_state 状态 0-未退款 1-申请中 2-退款成功
 * @property float $refund_fee 退款金额
 * @property string|null $refund_at 退款时间
 * @property int $card_id 会员卡ID
 * @property string $msg 描述
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class PayLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pay_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'state', 'type', 'pay_way', 'refund_state', 'card_id'], 'integer'],
            [['amount', 'refund_fee'], 'number'],
            [['end_at', 'refund_at', 'created_at', 'updated_at'], 'safe'],
            [['title', 'msg'], 'string', 'max' => 60],
            [['out_trade_no', 'trade_no'], 'string', 'max' => 64],
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
            'title' => 'Title',
            'amount' => 'Amount',
            'out_trade_no' => 'Out Trade No',
            'trade_no' => 'Trade No',
            'state' => 'State',
            'type' => 'Type',
            'pay_way' => 'Pay Way',
            'end_at' => 'End At',
            'refund_state' => 'Refund State',
            'refund_fee' => 'Refund Fee',
            'refund_at' => 'Refund At',
            'card_id' => 'Card ID',
            'msg' => 'Msg',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    public function getMember()
    {
        return $this->hasOne(MemberCard::class, ['id' => 'card_id']);
    }
}
