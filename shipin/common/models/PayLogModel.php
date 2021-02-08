<?php

namespace common\models;


use common\bases\CommonModel;

class PayLogModel extends CommonModel
{

    const TYPE_PAY_VIP = 1; // 购买会员
    const TYPE_PAY_NUM = 2; // 购买次数会员

    const STATE_WAITING = 0; // 等待支付
    const STATE_SUCCESS = 1; // 支付成功
    const STATE_FAILURE = 2; // 支付失败

    const REFUND_STATE_DEFAULT = 0; // 未申请退款
    const REFUND_STATE_APPLY = 1; // 申请退款中
    const REFUND_STATE_SUCCESS = 2; // 申请退款中

    const PAY_WAY_ALI = 1; // 支付类型 支付宝支付
    const PAY_WAY_WECHAT = 2; // 支付类型 微信支付
    const PAY_WAY_WX_APPLET = 3; // 支付类型 微信小程序
    const PAY_WAY_WX_OFFICIAL_ACCOUNTS = 4; // 支付类型 微信公众号

    const PAY_INTERVAL = 15; // 支付间隔时间s

    /**
     * 添加
     * @param array $data
     * @return bool|PayLog
     */
    public static function add($data)
    {
        $model = new PayLog();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        }
        return false;
    }

    /**
     * 更新
     * @param array $cond 查找条件
     * @param array $data 更新参数
     * @return bool
     */
    public static function updateByCond($cond, $data)
    {
        $model = PayLog::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据条件查找
     * @param array $cond
     * @return array|null|\yii\db\ActiveRecord|PayLog
     */
    public static function findOneByCond($cond)
    {
        return PayLog::find()->where($cond)->orderBy(['id' => SORT_DESC])->one();
    }

    /**
     * 获取列表
     * @param $limit
     * @param $offset
     * @param $userId
     * @param $type
     * @return array
     */
    public static function getList($limit, $offset, $userId, $outTradeNo, $payWay, $type = '')
    {
        $model = PayLog::find()
            ->with('user', 'member');
           //  ->where(['type' => $type]);
        if (!empty($userId)) {
            $model->andWhere(['user_id' => $userId]);
        }
        if ($type) {
            $model->andWhere(['type' => $type]);
        }
        if ($outTradeNo) {
            $model->andWhere(['out_trade_no' => $outTradeNo]);
        }
        if ($payWay) {
            $model->andWhere(['pay_way' => $payWay]);
        }
        return [
            'count' => (int)$model->count('id'),
            'list' => $model->limit($limit)->offset($offset)->orderBy(['id' => SORT_DESC])->all()
        ];
    }
}