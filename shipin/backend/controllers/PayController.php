<?php

namespace backend\controllers;


use AlibabaCloud\Tea\Exception\TeaError;
use backend\bases\BackendController;
use common\models\PayLogModel;
use common\services\AliService;
use common\services\UserService;
use common\services\WxService;
use yii\base\Exception;
use Yii;
use yii\helpers\Json;

class PayController extends BackendController
{
    const PAY_INTERVAL = 120; // 120s后的订单才可查询

    /**
     * 支付查单
     * @return string
     */
    public function actionQuery()
    {
        $request = Yii::$app->request;
        $id = $request->post('id', 0);
        $payLog = PayLogModel::findOneByCond(['id' => $id]);
        if (!$payLog) {
            return self::err('记录不存在');
        }
        if ($payLog->state !== PayLogModel::STATE_WAITING) {
            return self::err('记录状态不允许查询');
        }
        if ((time() - strtotime($payLog->created_at)) <= self::PAY_INTERVAL) {
            return self::err(sprintf('%s秒内的订单不可查询', self::PAY_INTERVAL));
        }
        if ($payLog->pay_way == PayLogModel::PAY_WAY_ALI) {
            try {
                $ali = new AliService();
                $resp = $ali->query($payLog->out_trade_no);
            } catch (TeaError $e) {
                return self::err($e->message);
            } catch (Exception $e) {
                return self::err('系统错误');
            }
            if ($resp->code == AliService::API_ERROR_CODE) {
                if ($resp->subCode == AliService::TRADE_NOT_EXIST) {
                    PayLogModel::updateByCond(['id' => $payLog->id], [
                        'state' => PayLogModel::STATE_FAILURE,
                        'msg' => sprintf('%s_%s', $payLog->msg, $resp->subMsg),
                        'end_at' => date('Y-m-d H:i:s'),
                    ]);
                    return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                }
                return self::err(sprintf('code:%s, 描述：%s', $resp->subCode ?? '', $resp->subMsg));
            } elseif ($resp->code == AliService::API_SUCCESS_CODE) {
                if ($resp->tradeStatus == AliService::STATE_TRADE_CLOSED) {
                    if ($payLog->state != PayLogModel::STATE_FAILURE) {
                        UserService::payFail($payLog, $resp->tradeNo ?? '', $resp->tradeStatus ?? '');
                        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                    }
                }
                if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                    if (in_array($resp->tradeStatus, [AliService::STATE_TRADE_FINISHED, AliService::STATE_TRADE_SUCCESS])) {
                        $ret = UserService::payMemberSuccess($payLog, $resp->tradeNo ?? ''); // 购买会员成功信息更新
                        if (!$ret['status']) {
                            return self::err($ret['msg']);
                        }
                        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                    }
                } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                    if (in_array($resp->tradeStatus, [AliService::STATE_TRADE_FINISHED, AliService::STATE_TRADE_SUCCESS])) {
                        $ret = UserService::payNumSuccess($payLog, $resp->tradeNo ?? ''); // 购买次数会员成功信息更新
                        if (!$ret['status']) {
                            return self::err($ret['msg']);
                        }
                        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                    }
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付类型错误']);
                }
            }
        } elseif (in_array($payLog->pay_way, [PayLogModel::PAY_WAY_WECHAT, PayLogModel::PAY_WAY_WX_APPLET, PayLogModel::PAY_WAY_WX_OFFICIAL_ACCOUNTS])) {
            try {
                $resp = WxService::query($payLog->out_trade_no, $payLog->pay_way, $payLog->user->wx_id ?? 0);
            } catch (Exception $e) {
                return self::err('系统错误');
            }
            if (!$resp['state']) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $resp['msg']]);
            }
            $data = $resp['data'];
            if ($data['return_code'] == WxService::SUCCESS) {
                if ($data['result_code'] == WxService::SUCCESS) {
                    // 已经退款
                    if ($data['trade_state'] == WxService::TRADE_STATE_REFUND) {
                        PayLogModel::updateByCond(['id' => $payLog->id], [
                            'state' => PayLogModel::STATE_SUCCESS,
                            'msg' => sprintf('%s_%s', $payLog->msg, $data['trade_state_desc'] ?? ''),
                            'end_at' => date('Y-m-d H:i:s'),
                            'refund_state' => PayLogModel::REFUND_STATE_SUCCESS,
                            'refund_fee' => $payLog->amount,
                            'refund_at' => date('Y-m-d H:i:s'),
                            'trade_no' => $data['transaction_id']
                        ]);
                        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                    } elseif ($data['trade_state'] == WxService::TRADE_STATE_SUCCESS) { // 支付成功
                        if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                            $ret = UserService::payMemberSuccess($payLog, $data['transaction_id'] ?? ''); // 购买会员成功信息更新
                            if (!$ret['status']) {
                                return self::err($ret['msg']);
                            }
                            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                        } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                            $ret = UserService::payNumSuccess($payLog, $data['transaction_id'] ?? ''); // 购买会员成功信息更新
                            if (!$ret['status']) {
                                return self::err($ret['msg']);
                            }
                            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                        } else {
                            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付类型不存在']);
                        }
                    } elseif (in_array($data['trade_state'], [WxService::TRADE_STATE_CLOSED, WxService::TRADE_STATE_NOTPAY, WxService::TRADE_STATE_PAYERROR])) {
                        if ($payLog->state != PayLogModel::STATE_FAILURE) {
                            UserService::payFail($payLog, $data['transaction_id'] ?? '', ($data['trade_state'] ?? '') . ($data['trade_state_desc'] ?? ''));
                            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                        }
                    } else {
                        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $data['trade_state_desc']]);
                    }
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $data['err_code'] ?? '' . $data['err_code_des'] ?? '']);
                }

            } else {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $data['return_msg'] ?? '未知错误']);
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '未知查询']);
    }

    /**
     *退款
     * @return string
     */
    public function actionRefund()
    {
        $request = Yii::$app->request;
        $id = $request->post('id', 0);
        $payLog = PayLogModel::findOneByCond(['id' => $id]);
        if (!$payLog) {
            return self::err('记录不存在');
        }
        if ($payLog->state != PayLogModel::STATE_SUCCESS) {
            return self::err('记录状态不允许退款');
        }
        if (!in_array($payLog->type, [PayLogModel::TYPE_PAY_VIP, PayLogModel::TYPE_PAY_NUM])) {
            return self::err('该类型不支持退款操作');
        }
        if ($payLog->refund_state != PayLogModel::REFUND_STATE_DEFAULT) {
            return self::err('记录状态不允许退款');
        }
        if ((time() - strtotime($payLog->created_at)) <= self::PAY_INTERVAL) {
            return self::err(sprintf('%s秒内的订单不可退款', self::PAY_INTERVAL));
        }
        if ($payLog->pay_way == PayLogModel::PAY_WAY_ALI) {
            try {
                $ali = new AliService();
                $resp = $ali->refund($payLog->out_trade_no, $payLog->amount);
                Yii::info(sprintf('退款操作返回: %s', Json::encode($resp)), 'ali');
            } catch (TeaError $e) {
                return self::err($e->message);
            } catch (Exception $e) {
                return self::err('系统错误');
            }
            if ($resp->code == AliService::API_SUCCESS_CODE) {
                if($resp->fundChange == AliService::FUND_CHANGE_Y) {
                    if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                        $ret = UserService::buyMemberRefundSuccess($payLog, $resp->refundFee ?? 0);
                    } else {
                        $ret = UserService::buyNumRefundSuccess($payLog, $resp->refundFee ?? 0);
                    }
                    if (!$ret['status']) {
                        return self::err($ret['msg']);
                    }
                    if ($ret) {
                        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '退款成功']);
                    }
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '本次退款没有发生资金变化']);
                }
            } else {
                Yii::error(sprintf('退款操作返回码异常: %s', Json::encode($resp)), 'ali');
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => sprintf('操作异常：%s,%s', $resp->code, $resp->subMsg)]);
            }
        } elseif (in_array($payLog->pay_way, [PayLogModel::PAY_WAY_WECHAT, PayLogModel::PAY_WAY_WX_APPLET, PayLogModel::PAY_WAY_WX_OFFICIAL_ACCOUNTS])) {
            try {
                $resp = WxService::refund($payLog->out_trade_no, $payLog->amount * 100, $payLog->amount * 100, $payLog->pay_way, $payLog->user->wx_id ?? 0);
                Yii::info(sprintf('退款操作返回: %s', Json::encode($resp)), 'wx');
            } catch (Exception $e) {
                return self::err('系统错误');
            }
            if (!$resp['state']) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $resp['msg'] ??  '退款失败']);
            }
            $data = $resp['data'];
            if ($data['return_code'] == WxService::SUCCESS) {
                if ($data['result_code'] == WxService::SUCCESS) {
                    PayLogModel::updateByCond(['id' => $payLog->id], [
                        'refund_state' => PayLogModel::REFUND_STATE_APPLY,
                    ]);
                    return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '退款申请成功']);
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => ($data['err_code'] ?? '') . ($data['err_code_des'] ?? '')]);
                }
            }
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $data['return_msg'] ?? '退款失败']);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '本次退款没有发生资金变化']);
    }
}