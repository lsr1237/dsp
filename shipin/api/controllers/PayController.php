<?php

namespace api\controllers;


use AlibabaCloud\Tea\Exception\TeaError;
use api\bases\ApiController;
use common\bases\CommonService;
use common\extend\Tool;
use common\extend\utils\IPUtils;
use common\extend\wx\AppletConfig;
use common\extend\wx\AppletPay;
use common\models\MemberCardModel;
use common\models\PayLogModel;
use common\models\UserModel;
use common\services\AliService;
use common\services\UserService;
use common\services\WxService;
use Yii;
use yii\helpers\Json;
use yii\base\Exception;

class PayController extends ApiController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => ['ali-callback', 'wx-callback', 'wx-refund-callback'],
                'allow' => true,
                'roles' => ['?'],
            ],
            // 其它的Action必须要授权用户才可访问
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ];
        return $behaviors;
    }

    /**
     * 购买会员确认
     * @return string
     */
    public function actionConfirm()
    {
        $request = Yii::$app->request;
        $id = (int)$request->post('id', 0);
        $cardType = (int)$request->post('card_type', 0);
        $card = MemberCardModel::findOneByCond(['id' => $id]);
        if (!$card) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '会查询到相关会员信息']);
        }
        if ($cardType != $card->type) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '会员卡类型不匹配']);
        }
        if ($card->state == MemberCardModel::STATE_INACTIVE) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '该类型会员已下线']);
        }
        $user = Yii::$app->user->identity;
        if ($card->wx_id != $user->wx_id) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '会员卡信息错误']);
        }
        $isMember = UserService::isMember($user); // 判断用户会员是否过期
        if ($isMember) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '您已开通会员']);
        }
        // $num = UserService::userNum($user);
//        if ($user->num > 0) {
//            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '您的可使用次数不为0']);
//        }
        $data = [
            'id' => $card->id,
            'price' => $card->cur_price,
            'name' => $card->name,
            'term' => $card->term,
            'num' => $card->num
        ];
        return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => [$data]]);
    }

    /**
     * 购买会员请求订单信息
     * @return string
     */
    public function actionSubmit()
    {
        $request = Yii::$app->request;
        $id = (int)$request->post('id', 0);
        $cardType = (int)$request->post('card_type', 0);
        $type = (int)$request->post('type', 0);
        if (!in_array($type, [
            PayLogModel::PAY_WAY_ALI,
            PayLogModel::PAY_WAY_WECHAT,
            PayLogModel::PAY_WAY_WX_APPLET,
            PayLogModel::PAY_WAY_WX_OFFICIAL_ACCOUNTS
        ])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付类型错误']);
        }
        $card = MemberCardModel::findOneByCond(['id' => $id]);
        if (!$card) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '会查询到相关会员信息']);
        }
        if ($card->state == MemberCardModel::STATE_INACTIVE) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '该类型会员已下线']);
        }
        if ($cardType != $card->type) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '会员卡类型不匹配']);
        }
        $user = Yii::$app->user->identity;
        if ($card->wx_id != $user->wx_id) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '会员卡信息错误']);
        }
        $isMember = UserService::isMember($user); // 判断用户会员是否过期
        if ($isMember) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '您已开通会员']);
        }
        // $num = UserService::userNum($user);
//        if ($user->num > 0) {
//            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '您的可使用次数不为0']);
//        }
        $amount = $card->cur_price;
        if ($amount < 0.01) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '当前价格不允许进行交易']);
        }
        $payLog = PayLogModel::findOneByCond(['user_id' => $user->id, 'state' => PayLogModel::STATE_WAITING]);
        if ($payLog && (time() - strtotime($payLog->updated_at)) <= PayLogModel::PAY_INTERVAL) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '您的操作过于频繁，请稍后再试',
            ]);
        }
        if ($type == PayLogModel::PAY_WAY_WX_APPLET) {
            $code = trim($request->post('code', ''));
            if (!$code) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => 'code不能为空']);
            }
        }
        $lockKeyUser = sprintf('%s_%s', self::KEY_USER, $user->id);
        try {
            $mutex = Yii::$app->mutex;
            $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
        } catch (Exception $e) {
            Yii::error('Redis服务异常：' . $e->getMessage());
            CommonService::sendDingMsg('Redis服务异常');
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
        }
        if ($lockUser) {
            if ($type == PayLogModel::PAY_WAY_ALI) {
                $ali = new AliService();
                $outTradeNo = Tool::getOrderNo($user->id);
                $ret = $ali->pay($card->name, $outTradeNo, $amount);
                if (!$ret->body) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '订单信息创建失败']);
                }
                // 创建支付记录
                $payLog = PayLogModel::add([
                    'title' => $card->name,
                    'user_id' => $user->id,
                    'card_id' => $card->id,
                    'amount' => $amount,
                    'out_trade_no' => $outTradeNo,
                    'type' => $card->type,
                    'pay_way' => PayLogModel::PAY_WAY_ALI,
                ]);
                if (!$payLog) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付信息保存失败']);
                }
                $mutex->release($lockKeyUser); // 释放锁
                return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => ['body' => $ret->body, 'id' => $payLog->id]]);
            } elseif ($type == PayLogModel::PAY_WAY_WECHAT) {
                // 微信支付
                $ip = IPUtils::getUserIP();
                $outTradeNo = Tool::getOrderNo($user->id);
                $wxRet = WxService::wxPay($card->name, $outTradeNo, $amount * 100, $ip);
                if (!$wxRet) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '订单信息创建失败']);
                }
                // 创建支付记录
                $payLog = PayLogModel::add([
                    'title' => $card->name,
                    'user_id' => $user->id,
                    'card_id' => $card->id,
                    'amount' => $amount,
                    'out_trade_no' => $outTradeNo,
                    'type' => $card->type,
                    'pay_way' => PayLogModel::PAY_WAY_WECHAT,
                ]);
                if (!$payLog) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付信息保存失败']);
                }
                $mutex->release($lockKeyUser); // 释放锁
                return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => ['body' => $wxRet, 'id' => $payLog->id]]);
            } elseif ($type == PayLogModel::PAY_WAY_WX_APPLET) {
                // 微信支付-小程序
                $outTradeNo = Tool::getOrderNo($user->id);
                $applet = new AppletPay($user->wx_id);
                $auth = $applet->getAuth($code);
                if (!$auth) {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '微信授权信息获取失败']);
                }
                $wxRet = $applet->unifiedOrder($card->name, $outTradeNo, $amount * 100, $auth->openid);
                if (!$wxRet['state']) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '订单信息创建失败']);
                }
                // 创建支付记录
                $payLog = PayLogModel::add([
                    'title' => $card->name,
                    'user_id' => $user->id,
                    'card_id' => $card->id,
                    'amount' => $amount,
                    'out_trade_no' => $outTradeNo,
                    'type' => $card->type,
                    'pay_way' => PayLogModel::PAY_WAY_WX_APPLET,
                ]);
                if (!$payLog) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付信息保存失败']);
                }
                $mutex->release($lockKeyUser); // 释放锁
                return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => ['body' => $wxRet['data'], 'id' => $payLog->id]]);
            } elseif ($type == PayLogModel::PAY_WAY_WX_OFFICIAL_ACCOUNTS) {
                // 微信支付-公众号
                $outTradeNo = Tool::getOrderNo($user->id);
                $officialId = AppletConfig::OFFICIAL_APPLET[$user->wx_id] ?? 0;
                $applet = new AppletPay($officialId);
                $wxRet = $applet->unifiedOrder($card->name, $outTradeNo, $amount * 100, $user->official_openid);
                if (!$wxRet['state']) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '订单信息创建失败']);
                }
                // 创建支付记录
                $payLog = PayLogModel::add([
                    'title' => $card->name,
                    'user_id' => $user->id,
                    'card_id' => $card->id,
                    'amount' => $amount,
                    'out_trade_no' => $outTradeNo,
                    'type' => $card->type,
                    'pay_way' => PayLogModel::PAY_WAY_WX_OFFICIAL_ACCOUNTS,
                ]);
                if (!$payLog) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付信息保存失败']);
                }
                $mutex->release($lockKeyUser); // 释放锁
                return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => ['body' => $wxRet['data'], 'id' => $payLog->id]]);
            } else {
                $mutex->release($lockKeyUser); // 释放锁
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统繁忙请稍后再试']);
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统繁忙请稍后再试']);
    }

    /**
     * 支付宝回调通知
     * @return int|string
     */
    public function actionAliCallback()
    {
        $request = Yii::$app->request;
        $data = $request->post();
        $str = Json::encode($data);
        Yii::info(sprintf("支付回调：\t\n %s", $str), 'ali');
        if (!$data) {
            Yii::error('回调内容不存在', 'ali');
            return 0;
        }
        $ali = new AliService();
        $signVerified = $ali->verify($data);

        if (!$signVerified) {
            Yii::error('回调通知参数签名验证失败', 'ali');
            CommonService::sendDingMsg('回调通知参数签名验证失败');
            return 0;
        }
        $payLog = PayLogModel::findOneByCond(['out_trade_no' => $data['out_trade_no']]);
        if (!$payLog) {
            Yii::error(sprintf('商户订单号：%s，支付记录不存在', $data['out_trade_no']), 'ali');
            return 0;
        }
        if ($payLog->amount != $data['total_amount']) {
            Yii::error(sprintf('商户订单号：%s，交易金额异常', $data['out_trade_no']), 'ali');
            return 0;
        }
        if ($data['seller_id'] != $ali->sellerId) {
            Yii::error(sprintf('商户订单号：%s，商户UID异常', $data['out_trade_no']), 'ali');
            return 0;
        }
        if ($data['app_id'] != $ali->appId) {
            Yii::error(sprintf('商户订单号：%s，商户appID异常', $data['out_trade_no']), 'ali');
            return 0;
        }
        if ($payLog->state == PayLogModel::STATE_WAITING) {
            if ($data['trade_status'] == AliService::STATE_TRADE_CLOSED) {
                if ($payLog->state != PayLogModel::STATE_FAILURE) {
                    UserService::payFail($payLog, $data['trade_no'] ?? '', $data['trade_status'] ?? ''); // 支付失败信息变更
                }
                return 'success';
            }
            if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                // 会员购买成功
                if (in_array($data['trade_status'], [AliService::STATE_TRADE_FINISHED, AliService::STATE_TRADE_SUCCESS])) {
                    if ($payLog->state != PayLogModel::STATE_SUCCESS) {
                        $ret = UserService::payMemberSuccess($payLog, $data['trade_no'] ?? ''); // 购买会员成功信息更新
                        if (!$ret['status']) {
                            CommonService::sendDingMsg(sprintf('支付宝支付回调错误信息：%s', $ret['msg']));
                            return 0;
                        }
                    }
                }
            } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                // 购买次数成功
                if (in_array($data['trade_status'], [AliService::STATE_TRADE_FINISHED, AliService::STATE_TRADE_SUCCESS])) {
                    if ($payLog->state != PayLogModel::STATE_SUCCESS) {
                        $ret = UserService::payNumSuccess($payLog, $data['trade_no'] ?? ''); // 购买会员成功信息更新
                        if (!$ret['status']) {
                            CommonService::sendDingMsg(sprintf('支付宝支付回调错误信息：%s', $ret['msg']));
                            return 0;
                        }
                    }
                }
            }
        } elseif ($payLog->state == PayLogModel::STATE_SUCCESS) {
            // 支付成功退款回调
            if ($data['trade_status'] == AliService::STATE_TRADE_CLOSED) {
                Yii::info(sprintf('商户订单号：%s，交易已关闭', $data['out_trade_no']), 'ali');
            }
        } else {
            Yii::error(sprintf('商户订单号：%s，订单状态已改变', $data['out_trade_no']), 'ali');
            return 'success';
        }
        return 'success';
    }

    /**
     * 微信支付回调
     * @return string
     */
    public function actionWxCallback()
    {
        $xml = file_get_contents("php://input");
        if ($xml == null) {
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        }
        Yii::info(sprintf("微信支付回调： \n %s", $xml), 'wx');
        libxml_disable_entity_loader(true);
        $data = WxService::xmlToArray($xml);
        if (!$data) {
            Yii::error('微信回调数据为空', 'wx');
            return WxService::successTxt();
        }
        $appId = $data['appid'] ?? '';
        $checkSign = WxService::checkNotifySign($data, $appId);
        if (!$checkSign) {
            Yii::error('签名验证失败', 'wx');
            return WxService::successTxt();
        }
        if (!in_array($data['appid'], WxService::APP_ID_PAY_MAP)) {
            Yii::error('微信支付回调：APPID不匹配', 'wx');
            return WxService::successTxt();
        }
        $payLog = PayLogModel::findOneByCond(['out_trade_no' => $data['out_trade_no']]);
        if (!$payLog) {
            Yii::error(sprintf('商户订单号：%s，支付记录不存在', $data['out_trade_no']), 'wx');
            return WxService::successTxt();
        }
        if ($payLog->state == PayLogModel::STATE_WAITING) {
            if ($data['return_code'] == WxService::SUCCESS) {
                if ($data['result_code'] == WxService::FAIL) {
                    if ($payLog->state != PayLogModel::STATE_FAILURE) {
                        UserService::payFail($payLog, $data['transaction_id'] ?? '', $data['result_code'] ?? ''); // 支付失败信息变更
                    }
                    return WxService::successTxt();
                }
                if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                    // 会员购买成功
                    if ($data['result_code'] == WxService::SUCCESS) {
                        if ($payLog->state != PayLogModel::STATE_SUCCESS) {
                            $ret = UserService::payMemberSuccess($payLog, $data['transaction_id'] ?? ''); // 购买会员成功信息更新
                            if (!$ret['status']) {
                                CommonService::sendDingMsg(sprintf('微信支付回调错误信息：%s', $ret['msg']));
                                return WxService::failTxt();
                            }
                        }
                    }
                } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                    // 购买次数成功
                    if ($data['result_code'] == WxService::SUCCESS) {
                        if ($payLog->state != PayLogModel::STATE_SUCCESS) {
                            $ret = UserService::payNumSuccess($payLog, $data['transaction_id'] ?? ''); // 购买会员成功信息更新
                            if (!$ret['status']) {
                                CommonService::sendDingMsg(sprintf('微信支付回调错误信息：%s', $ret['msg']));
                                return WxService::failTxt();
                            }
                        }
                    }
                }
            }
        } else {
            Yii::error(sprintf('商户订单号：%s，订单状态已改变', $data['out_trade_no']), 'wx');
            return WxService::successTxt();
        }
        return WxService::successTxt();
    }

    /**
     * 微信支付退款回调
     * @return string
     */
    public function actionWxRefundCallback()
    {
        $xml = file_get_contents("php://input");
        if ($xml == null) {
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        }
        Yii::info(sprintf("微信退款回调： \n %s", $xml), 'wx');
        libxml_disable_entity_loader(true);
        $data = WxService::xmlToArray($xml);
        if (!in_array($data['appid'], WxService::APP_ID_PAY_MAP)) {
            Yii::error('微信退款回调：APPID不匹配', 'wx');
            return WxService::successTxt();
        }
        if ($data['return_code'] == WxService::SUCCESS) {
            $repInfo = WxService::decryptAesData($data['req_info'] ?? '', $data['appid']);
            if ($repInfo) {
                $payLog = PayLogModel::findOneByCond(['out_trade_no' => $repInfo['out_trade_no']]);
                if (!$payLog) {
                    Yii::error(sprintf('微信退款回调,商户订单号：%s，支付记录不存在', $repInfo['out_trade_no']), 'wx');
                    return WxService::successTxt();
                }
                if ($payLog->state != PayLogModel::STATE_SUCCESS) {
                    Yii::error(sprintf('微信退款回调,商户订单号：%s，记录状态不为成功', $repInfo['out_trade_no']), 'wx');
                    return WxService::failTxt();
                }
                if ($payLog->refund_state == PayLogModel::REFUND_STATE_APPLY) {
                    if ($repInfo['refund_status'] == WxService::REFUND_STATE_SUCCESS) {
                        if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                            $ret = UserService::buyMemberRefundSuccess($payLog, round(($repInfo['refund_fee'] ?? 0) / 100, 2));
                            if (!$ret['status']) {
                                CommonService::sendDingMsg(sprintf('微信支付退款回调错误信息：%s', $ret['msg']));
                                return WxService::failTxt();
                            }
                        } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                            $ret = UserService::buyNumRefundSuccess($payLog, round(($repInfo['refund_fee'] ?? 0) / 100, 2));
                            if (!$ret['status']) {
                                CommonService::sendDingMsg(sprintf('微信支付退款回调错误信息：%s', $ret['msg']));
                                return WxService::failTxt();
                            }
                        }
                    } elseif (in_array($repInfo['refund_status'], [WxService::REFUND_STATE_CHANGE, WxService::REFUND_STATE_REFUNDCLOSE])) {
                        PayLogModel::updateByCond(['id' => $payLog->id], ['refund_state' => PayLogModel::REFUND_STATE_DEFAULT]);
                    }
                }
            } else {
                Yii::error('微信退款回调：解密信息不存在', 'wx');
                return WxService::failTxt();
            }
        } else {
            Yii::error('微信退款回调：return_code字段不为SUCCESS', 'wx');
        }
        return WxService::successTxt();
    }

    /**
     * 支付信息查询成功才有返回
     * @return string
     */
    public function actionQuery()
    {
        $request = Yii::$app->request;
        $id = $request->post('id', 0);
        if (!$id) {
            return self::err('ID参数错误');
        }
        $user = Yii::$app->user->identity;
        $payLog = PayLogModel::findOneByCond(['id' => $id, 'user_id' => $user->id]);
        if (!$payLog) {
            return self::err('支付记录不存在');
        }
        $data = [
            'pay_state' => $payLog->state,
            'is_member' => UserService::isMember($user),
            'num' => UserService::userNum($user)
        ];
        if ($payLog->state == PayLogModel::STATE_SUCCESS) {
            return self::success(['results' => [$data]]);
        }
        if ($payLog->state == PayLogModel::STATE_FAILURE) {
            return self::err('支付失败');
        }
        // 支付等待中查单
        $lockKeyUser = sprintf('%s_%s', self::KEY_USER, $user->id);
        try {
            $mutex = Yii::$app->mutex;
            $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
        } catch (Exception $e) {
            Yii::error('Redis服务异常：' . $e->getMessage());
            CommonService::sendDingMsg('Redis服务异常');
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
        }
        if ($lockUser) {
            if ($payLog->pay_way == PayLogModel::PAY_WAY_ALI) {
                try {
                    $ali = new AliService();
                    $resp = $ali->query($payLog->out_trade_no);
                } catch (TeaError $e) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return self::err('系统错误');
                } catch (Exception $e) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return self::err('系统错误');
                }
                if ($resp->code == AliService::API_ERROR_CODE) {
                    if ($resp->subCode == AliService::TRADE_NOT_EXIST) {
                        PayLogModel::updateByCond(['id' => $payLog->id], [
                            'state' => PayLogModel::STATE_FAILURE,
                            'msg' => sprintf('%s_%s', $payLog->msg, $resp->subMsg),
                            'end_at' => date('Y-m-d H:i:s'),
                        ]);
                        $mutex->release($lockKeyUser); // 释放锁
                        return self::err('支付失败');
                    }
                    $mutex->release($lockKeyUser); // 释放锁
                    return self::err(sprintf('code:%s, 描述：%s', $resp->subCode ?? '', $resp->subMsg));
                } elseif ($resp->code == AliService::API_SUCCESS_CODE) {
                    if ($resp->tradeStatus == AliService::STATE_TRADE_CLOSED) {
                        if ($payLog->state != PayLogModel::STATE_FAILURE) {
                            UserService::payFail($payLog, $resp->tradeNo ?? '', $resp->tradeStatus ?? '');
                            $mutex->release($lockKeyUser); // 释放锁
                            return self::err('支付失败');
                        }
                    }
                    if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                        if (in_array($resp->tradeStatus, [AliService::STATE_TRADE_FINISHED, AliService::STATE_TRADE_SUCCESS])) {
                            $ret = UserService::payMemberSuccess($payLog, $resp->tradeNo ?? ''); // 购买会员成功信息更新
                            if (!$ret['status']) {
                                $mutex->release($lockKeyUser); // 释放锁
                                return self::err($ret['msg']);
                            }
                            $user = UserModel::findUserById($user->id);
                            $data = [
                                'pay_state' => PayLogModel::STATE_SUCCESS,
                                'is_member' => UserService::isMember($user),
                                'num' => UserService::userNum($user)
                            ];
                            $mutex->release($lockKeyUser); // 释放锁
                            return self::success(['results' => [$data]]);
                        }
                    } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                        if (in_array($resp->tradeStatus, [AliService::STATE_TRADE_FINISHED, AliService::STATE_TRADE_SUCCESS])) {
                            $ret = UserService::payNumSuccess($payLog, $resp->tradeNo ?? ''); // 购买次数会员成功信息更新
                            if (!$ret['status']) {
                                $mutex->release($lockKeyUser); // 释放锁
                                return self::err($ret['msg']);
                            }
                            $user = UserModel::findUserById($user->id);
                            $data = [
                                'pay_state' => PayLogModel::STATE_SUCCESS,
                                'is_member' => UserService::isMember($user),
                                'num' => UserService::userNum($user)
                            ];
                            $mutex->release($lockKeyUser); // 释放锁
                            return self::success(['results' => [$data]]);
                        }
                    } else {
                        $mutex->release($lockKeyUser); // 释放锁
                        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '支付类型错误']);
                    }
                }
            } elseif (in_array($payLog->pay_way, [PayLogModel::PAY_WAY_WECHAT, PayLogModel::PAY_WAY_WX_APPLET, PayLogModel::PAY_WAY_WX_OFFICIAL_ACCOUNTS])) {
                try {
                    $resp = WxService::query($payLog->out_trade_no, $payLog->pay_way, $user->wx_id ?? 0);
                } catch (Exception $e) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return self::err('系统错误');
                }
                if (!$resp['state']) {
                    $mutex->release($lockKeyUser); // 释放锁
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
                            $mutex->release($lockKeyUser); // 释放锁
                            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '订单查询成功']);
                        } elseif ($data['trade_state'] == WxService::TRADE_STATE_SUCCESS) { // 支付成功
                            if ($payLog->type == PayLogModel::TYPE_PAY_VIP) {
                                $ret = UserService::payMemberSuccess($payLog, $data['transaction_id'] ?? ''); // 购买会员成功信息更新
                                if (!$ret['status']) {
                                    $mutex->release($lockKeyUser); // 释放锁
                                    return self::err($ret['msg']);
                                }
                                $user = UserModel::findUserById($user->id);
                                $data = [
                                    'pay_state' => PayLogModel::STATE_SUCCESS,
                                    'is_member' => UserService::isMember($user),
                                    'num' => UserService::userNum($user)
                                ];
                                $mutex->release($lockKeyUser); // 释放锁
                                return self::success(['results' => [$data]]);
                            } elseif ($payLog->type == PayLogModel::TYPE_PAY_NUM) {
                                $ret = UserService::payNumSuccess($payLog, $data['transaction_id'] ?? ''); // 购买会员成功信息更新
                                if (!$ret['status']) {
                                    $mutex->release($lockKeyUser); // 释放锁
                                    return self::err($ret['msg']);
                                }
                                $user = UserModel::findUserById($user->id);
                                $data = [
                                    'pay_state' => PayLogModel::STATE_SUCCESS,
                                    'is_member' => UserService::isMember($user),
                                    'num' => UserService::userNum($user)
                                ];
                                $mutex->release($lockKeyUser); // 释放锁
                                return self::success(['results' => [$data]]);
                            } else {
                                $mutex->release($lockKeyUser); // 释放锁
                                return self::err('支付类型不存在');
                            }
                        } elseif (in_array($data['trade_state'], [WxService::TRADE_STATE_CLOSED, WxService::TRADE_STATE_NOTPAY, WxService::TRADE_STATE_PAYERROR])) {
                            if ($payLog->state != PayLogModel::STATE_FAILURE) {
                                UserService::payFail($payLog, $data['transaction_id'] ?? '', ($data['trade_state'] ?? '') . ($data['trade_state_desc'] ?? ''));
                                $mutex->release($lockKeyUser); // 释放锁
                                return self::err('支付失败');
                            }
                        }
                    }
                }
                $mutex->release($lockKeyUser); // 释放锁
                return self::err('订单信息未错误');
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统繁忙请稍后再试']);
    }
}