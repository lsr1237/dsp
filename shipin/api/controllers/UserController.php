<?php

namespace api\controllers;


use common\bases\CommonService;
use common\extend\Tool;
use common\models\NumLogModel;
use common\services\RedisService;
use common\services\UserService;
use Yii;
use api\bases\ApiController;
use yii\base\Exception;
use yii\helpers\Json;

class UserController extends ApiController
{
    const TYPE_INVITE = 1; // 邀请好友
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => [''],
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
     * 我的
     * @return string
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isMember = UserService::isMember($user); // 判断用户会员是否过期
        $key = sprintf('%s_%s', RedisService::KEY_APP_BASIC, $user->wx_id);
        $openVipTxt = RedisService::hGet($key, 'open_vip_txt');
        if (!$openVipTxt) {
            $openVipTxt = '';
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => [
                [
                    'mobile' => !empty($user->mobile) ? Tool::desensitization($user->mobile, 3, 4) : '',
                    'is_member' => $isMember,
                    'end_at' => $isMember ? date('Y-m-d', strtotime($user->end_at)) : '',
                    'vip_name' => $isMember ? '会员用户' : '普通用户',
                    'vip_id' => $user->number,
                    'open_vip_txt' => $openVipTxt,
                    'num' => UserService::userNum($user),
                    'surplus_num' => UserService::surplusAdNum($user)
                ]
            ]
        ]);
    }

    /**
     * 观看激励视频广告成功添加次数
     * @return string
     */
    public function actionAdReward()
    {
        $user = Yii::$app->user->identity;
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
            $surplusAdNum = UserService::surplusAdNum($user);
            try {
                if ($surplusAdNum > 0) {
                    $user = UserService::finishedAdReward($user);
                    if ($user) {
                        $mutex->release($lockKeyUser); // 释放锁
                        return Json::encode([
                            'status' => self::STATUS_SUCCESS,
                            'error_message' => '完成任务次数+1',
                            'results' => [
                                [
                                    'num' => UserService::userNum($user),
                                    'surplus_num' => UserService::surplusAdNum($user)
                                ]
                            ]
                        ]);
                    }
                } else {
                    $mutex->release($lockKeyUser); // 释放锁
                    return self::err('您今日已的可任务次数已达上限');
                }
                $mutex->release($lockKeyUser); // 释放锁
                return self::err('奖励发放失败');
            } catch (Exception $e) {
                $mutex->release($lockKeyUser); // 释放锁
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统繁忙请稍后再试']);
    }

    /**
     * 获取次数列表
     * @return string
     */
    public function actionNumLog()
    {
        $user = Yii::$app->user->identity;
        $request = Yii::$app->request;
        $pn = (int)$request->get('pn', 1); // 页数
        $type = (int)$request->get('type', 0); // 类型
        if ($pn >=5 ) {
            $pn = 5;
        }
        if ($type && $type == self::TYPE_INVITE) {
            $type = NumLogModel::TYPE_INVITE;
        }
        $limit = Yii::$app->params['page_limit'];
        $offset = ($pn - 1) * $limit;
        $ret = NumLogModel::getList($offset, $limit, $user->id, $type);
        foreach ($ret['list'] as $log) {
            $num = $log->num;
            if (in_array($log->type, NumLogModel::TYPE_MAP_NEGATIVE)) {
                $num = -$num;
            }
            $data[] = [
                'id' => $log->id,
                'title' => NumLogModel::TYPE_MAP[$log->type] ?? '',
                'created_at' => $log->created_at,
                'num' => $num
            ];
        }
        if (($offset + $limit) < $ret['count'] && ($offset + $limit) < 50) {
            $hasMore = true;
        } else {
            $hasMore = false;
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => $data ?? [],
            'has_more' => $hasMore,
        ]);
    }
}