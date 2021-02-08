<?php

namespace backend\services;

use backend\models\AdminModel;
use Yii;

class AdminService
{
    const LOCK_TIME = 1800; // 锁定时间:秒
    const LOCK_CNT = 5; // 错误次数大于等于5次锁定

    /**
     * 账户锁定
     * @param $user
     * @return string
     */
    public static function accountLock($user)
    {
        $user = AdminModel::findOneByCond(['id' => $user->id ?? 0]); // 更新用户信息
        if (!$user) {
            return '用户不存在，请联系管理员';
        }
        $isLock = false; // 是否锁定
        $errAt = $user->err_at ?? ''; // 上次输错密码时间
        $errCnt = $user->err_cnt ?? 0; // 上次输错密码累计次数
        if (!empty($errAt) && !empty($errCnt)) {
            // 验证时间是否为今日，若为今日累计，并自增验证次数
            if (date('Ymd') == date('Ymd', strtotime($errAt))) {
                $errCnt++; // 错误数自增1
                if ($errCnt >= self::LOCK_CNT) {
                    $isLock = true; // 锁定
                    $waitTime = round(self::LOCK_TIME / 60); // 应等待时间
                    $msg = sprintf('您的账户已被锁定，请%s分钟后重试', $waitTime);
                } else {
                    $errAt = date('Y-m-d H:i:s');
                    $surplusCnt = self::LOCK_CNT - $errCnt; // 剩余输入次数
                    $msg = sprintf('密码错误！您还可以输入%s次，错误超过%s次系统将自动锁定', $surplusCnt, self::LOCK_CNT);
                }
            } else {
                // 验证时间不为今日，重新计数
                $errCnt = 1;
                $errAt = date('Y-m-d H:i:s');
                $surplusCnt = self::LOCK_CNT - 1; // 剩余输入次数
                $msg = sprintf('密码错误！您还可以输入%s次，错误超过%s次系统将自动锁定', $surplusCnt, self::LOCK_CNT);
            }
        } else {
            $errCnt = 1;
            $errAt = date('Y-m-d H:i:s');
            $surplusCnt = self::LOCK_CNT - 1; // 剩余输入次数
            $msg = sprintf('密码错误！您还可以输入%s次，错误超过%s次系统将自动锁定', $surplusCnt, self::LOCK_CNT);
        }
        // 更新错误次数、时间、管理员状态
        $ret = AdminModel::update($user->id, [
            'err_at' => $errAt,
            'err_cnt' => $errCnt,
            'state' => $isLock ? AdminModel::LOCK : AdminModel::SIGNED,
        ]);
        if (!$ret) {
            $msg = '服务器错误，请联系管理员';
        }
        return $msg;
    }

    /**
     * 账户是否被锁定
     * @param $user
     * @return array
     */
    public static function isLock($user)
    {
        if (!$user) {
            return [
                'ret' => false,
                'msg' => '用户不存在，请联系管理员'
            ];
        }
        if ($user->state == AdminModel::LOCK) {
            $errAt = $user->err_at ?? ''; // 上次输错密码时间
            $errCnt = $user->err_cnt ?? 0; // 上次输错密码累计次数
            if (empty($errAt) || empty($errCnt)) {
                return [
                    'ret' => false,
                    'msg' => '已解锁或时间不正确'
                ];
            }
            $diffAt = (time() - strtotime($errAt)); // 间隔时间
            if ($diffAt >= self::LOCK_TIME) {
                $ret = AdminModel::update($user->id, [
                    'state' => AdminModel::SIGNED,
                    'err_at' => null,
                    'err_cnt' => 0,
                ]); // 解锁
                return [
                    'ret' => false,
                    'msg' => '已解锁'
                ];
            }
            return [
                'ret' => true,
                'msg' => sprintf('您的账户已被锁定，请%s分钟后重试', ceil((self::LOCK_TIME - $diffAt) / 60))
            ];
        }
        return [
            'ret' => false,
            'msg' => '未锁定'
        ];
    }
}