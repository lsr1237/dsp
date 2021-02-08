<?php

namespace common\services;


use common\bases\CommonService;
use common\extend\Tool;
use common\extend\wx\AppletConfig;
use common\models\AppBasicModel;
use common\models\MemberCardModel;
use common\models\NumLogModel;
use common\models\PayLog;
use common\models\PayLogModel;
use common\models\User;
use common\models\UserModel;
use Yii;
use yii\db\Exception as DBException;
use yii\base\Exception as BaseException;

class UserService extends CommonService
{
    const DAY_SECOND = 86400;

    /**
     * 判断会员是否过期
     * @param User $user
     * @return bool
     */
    public static function isMember($user)
    {
        if ($user) {
            if (strtotime($user->end_at) > time()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断是否可操作
     * @param User $user
     * @return array
     */
    public static function isExec($user)
    {
        if ($user) {
            $key = sprintf('%s_%s', RedisService::KEY_APP_BASIC, $user->wx_id);
            $auditState = (int)RedisService::hGet($key, 'audit_state'); // 当前审核状态为开启的时候用户直接获取视频不判断次数
            if ($auditState == AppBasicModel::AUDIT_STATE_OPEN) {
                return ['status' => self::STATUS_SUCCESS];
            }
            $isMember = self::isMember($user);
            if ($isMember) {
                return ['status' => self::STATUS_SUCCESS];
            }
            $today = date('Ymd');
            $key = sprintf('%s_%s_%s_%s', RedisService::KEY_TRIAL_CNT_APPLET, $today, $user->id, $user->wx_id);
            if ($user->num <= 0) {
                return [
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '观看广告免费下载'
                ];
            } elseif ($user->num > 0) {
                $ret = UserModel::updateByCond(['id' => $user->id], ['num' => $user->num - 1]);
                if (!$ret) {
                    return [
                        'status' => self::STATUS_FAILURE,
                        'error_message' => '用户可使用次数更新失败'
                    ];
                }
                return ['status' => self::STATUS_SUCCESS];
            }
        }
        return ['status' => self::STATUS_FAILURE, 'error_message' => '获取用户信息失败'];
    }

    /**
     * 获取用户当前可用次数
     * @param User $user
     * @return int|mixed
     */
    public static function userNum($user)
    {
        // $isMember = self::isMember($user);
        if ($user) {
            return $user->num;
        }
        return 0;
    }

    /**
     * 支付失败修改
     * @param PayLog $payLog 支付记录
     * @param string $tradeNo 第三方订单号
     * @param string $tradeStatus 支付状态
     */
    public static function payFail($payLog, $tradeNo, $tradeStatus)
    {
        PayLogModel::updateByCond(['id' => $payLog->id], [
            'state' => PayLogModel::STATE_FAILURE,
            'trade_no' => !empty($payLog->trade_no) ? $payLog->trade_no : $tradeNo,
            'msg' => sprintf('%s_%s', $payLog->msg, $tradeStatus),
            'end_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 购买会员成功信息修改
     * @param PayLog $payLog
     * @param string $tradeNo
     * @return array
     */
    public static function payMemberSuccess($payLog, $tradeNo)
    {
        $user = UserModel::findUserById($payLog->user_id);
        $card = MemberCardModel::findOneByCond(['id' => $payLog->card_id]);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 创建更改会员信息
            $ret1 = UserModel::updateByCond(
                ['id' => $user->id],
                [
                    'member_card_id' => $card->id,
                    'end_at' => date('Y-m-d H:i:s', time() + $card->term * 86400),
                    'number' => !empty($user->number) ? $user->number : Tool::getRandomString(9, 'abcdefghijklmnopqrstuvwxyz0123456789')
                ]
            );
            if (!$ret1) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('保存用户会员卡信息失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $ret2 = PayLogModel::updateByCond(['id' => $payLog->id], [
                'state' => PayLogModel::STATE_SUCCESS,
                'trade_no' => $tradeNo,
                'end_at' => date('Y-m-d H:i:s'),
                'msg' => sprintf('%s_支付成功', $payLog->msg)
            ]);
            if (!$ret2) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('支付信息更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $transaction->commit();
            return ['status' => true, 'msg' => '成功'];
        } catch (DBException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        } catch (BaseException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        }
    }

    /**
     * 购买次数成功信息修改
     * @param PayLog $payLog
     * @param string $tradeNo
     * @return array
     */
    public static function payNumSuccess($payLog, $tradeNo)
    {
        $user = UserModel::findUserById($payLog->user_id);
        $card = MemberCardModel::findOneByCond(['id' => $payLog->card_id]);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 创建更改会员信息
            $ret1 = UserModel::updateByCond(
                ['id' => $user->id],
                [
                    'num' => $card->num + $user->num,
                    // 'num' => $card->num,
                ]
            );
            if (!$ret1) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('保存用户会员次数信息失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $ret2 = PayLogModel::updateByCond(['id' => $payLog->id], [
                'state' => PayLogModel::STATE_SUCCESS,
                'trade_no' => $tradeNo,
                'end_at' => date('Y-m-d H:i:s'),
                'msg' => sprintf('%s_支付成功', $payLog->msg)
            ]);
            if (!$ret2) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('支付信息更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $ret3 = NumLogModel::add(['user_id' => $user->id, 'num' => $card->num, 'type' => NumLogModel::TYPE_BUY]);
            if (!$ret3) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('次数记录更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $transaction->commit();
            return ['status' => true, 'msg' => '成功'];
        } catch (DBException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        } catch (BaseException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        }
    }

    /**
     * 会员退款
     * @param PayLog $payLog
     * @param $refundFee
     * @return array
     */
    public static function buyMemberRefundSuccess($payLog, $refundFee)
    {
        $card = MemberCardModel::findOneByCond(['id' => $payLog->card_id]);
        $user = UserModel::findUserById($payLog->user_id);
        if (!$card) {
            return [
                'status' => false,
                'msg' => sprintf('未查询到会员卡信息，交易订单号:%s', $payLog->out_trade_no)
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $ret1 = PayLogModel::updateByCond(['id' => $payLog->id], [
                'refund_state' => PayLogModel::REFUND_STATE_SUCCESS,
                'refund_fee' => $refundFee,
                'refund_at' => date('Y-m-d H:i:s')
            ]);
            if (!$ret1) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('支付信息更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $ret2 = UserModel::updateByCond(['id' => $user->id], [
                'end_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$ret2) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('会员卡信息更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $transaction->commit();
            return ['status' => true, 'msg' => '成功'];
        } catch (DBException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        } catch (BaseException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        }
    }

    /**
     * 次数退款
     * @param PayLog $payLog
     * @param $refundFee
     * @return array
     */
    public static function buyNumRefundSuccess($payLog, $refundFee)
    {
        $card = MemberCardModel::findOneByCond(['id' => $payLog->card_id]);
        $user = UserModel::findUserById($payLog->user_id);
        if (!$card) {
            return [
                'status' => false,
                'msg' => sprintf('未查询到会员卡信息，交易订单号:%s', $payLog->out_trade_no)
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $ret1 = PayLogModel::updateByCond(['id' => $payLog->id], [
                'refund_state' => PayLogModel::REFUND_STATE_SUCCESS,
                'refund_fee' => $refundFee,
                'refund_at' => date('Y-m-d H:i:s')
            ]);
            if (!$ret1) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('支付信息更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $num = $user->num - $card->num;
            if ($num < 0) {
                $num = 0;
            }
            // $num = 0;
            $ret2 = UserModel::updateByCond(['id' => $user->id], [
                'num' => $num,
            ]);
            if (!$ret2) {
                $transaction->rollBack();
                return [
                    'status' => false,
                    'msg' => sprintf('会员卡信息更新失败，交易订单号:%s', $payLog->out_trade_no)
                ];
            }
            $transaction->commit();
            return ['status' => true, 'msg' => '成功'];
        } catch (DBException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        } catch (BaseException $e) {
            Yii::error($e);
            $transaction->rollBack();
            return ['status' => false, 'msg' => '数据出错了, 请联系客服'];
        }
    }

    /**
     * 邀请者添加邀请奖励
     * @param User $user
     * @return bool
     */
    public static function inviteReward($user)
    {
        if (!$user) {
            return false;
        }
        $rewardNum = (int)RedisService::hGet(sprintf('%s_%s', RedisService::KEY_APP_BASIC, $user->wx_id), 'reward_num');
        if ($rewardNum > 0) {
            $ret = UserModel::updateByCond(
                ['id' => $user->id],
                [
                    'num' => $rewardNum + $user->num
                ]
            );
            if (!$ret) {
                Yii::error("用户（{$user->id}）邀请奖励发放失败", 'user');
                return false;
            }
            NumLogModel::add(['user_id' => $user->id, 'num' => $rewardNum, 'type' => NumLogModel::TYPE_INVITE]);
        }
        return true;
    }

    /**
     * 完成广告任务添加次数
     * @param User $user
     * @return bool|User|null|static
     */
    public static function finishedAdReward($user)
    {
        $today = date('Ymd');
        $key = sprintf('%s_%s_%s_%s', RedisService::KEY_REWARD_WATCH_CNT, $today, $user->id, $user->wx_id);
        $usedCnt = (int)RedisService::getKey($key);
        if ($usedCnt == 0) {
            RedisService::setKeyWithExpire($key, 1, self::DAY_SECOND);
        } else {
            RedisService::incr($key);
        }
        $ret = UserModel::updateByCond(
            ['id' => $user->id],
            [
                'num' => 1 + $user->num
            ]
        );
        if (!$ret) {
            Yii::error("用户（{$user->id}）观看视频添加次数失败", 'user');
            return false;
        }
        NumLogModel::add(['user_id' => $user->id, 'num' => 1, 'type' => NumLogModel::TYPE_AD]);
        return $ret;
    }

    /**
     * 获取剩余可观看广告次数
     * @param User $user
     * @return int
     */
    public static function surplusAdNum($user)
    {
        $today = date('Ymd');
        $key = sprintf('%s_%s_%s_%s', RedisService::KEY_REWARD_WATCH_CNT, $today, $user->id, $user->wx_id);
        $usedCnt = (int)RedisService::getKey($key);
        $limitNum = (int)RedisService::hGet(sprintf('%s_%s', RedisService::KEY_APP_BASIC, $user->wx_id), 'limit_num');
        $surplusNum = $limitNum - $usedCnt;
        return $surplusNum > 0 ? $surplusNum : 0;
    }
}