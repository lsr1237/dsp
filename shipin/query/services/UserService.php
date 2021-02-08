<?php

namespace query\services;


use common\bases\CommonService;
use common\services\RedisService;

class UserService extends CommonService
{
    const DAY_SECOND = 86400;
    /**
     * 获取今日剩余查询次数
     * @param $user
     * @return bool|int|string
     */
    public static function availableNum($user)
    {
        $freeNum = (int)RedisService::hGet(RedisService::KEY_QUERY_CONF, 'free_num');
        $today = date('Ymd');
        $key = sprintf('%s_%s_%s', RedisService::KEY_QUERY_NUM, $today, $user->id);
        $todayQueryCnt = (int)RedisService::getKey($key);
        $availableNum = $freeNum - $todayQueryCnt;
        return $availableNum > 0 ? $availableNum : 0;
    }

    /**
     * 增加今天查询次数
     * @param $user
     */
    public static function incQueryCnt($user)
    {
        $today = date('Ymd');
        $key = sprintf('%s_%s_%s', RedisService::KEY_QUERY_NUM, $today, $user->id);
        $todayQueryCnt = (int)RedisService::getKey($key);
        if ($todayQueryCnt == 0) {
            RedisService::setKeyWithExpire($key, 1, self::DAY_SECOND);
        } else {
            RedisService::incr($key);
        }
    }
}