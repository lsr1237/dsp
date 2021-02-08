<?php

namespace common\services;

use Yii;

class RedisService
{
    const KEY_MEMBER_CARDS = 'member_card'; // 会员卡列表
    const KEY_BANNER = 'banner'; // 首页banner
    const KEY_APP_BASIC = 'app_basic'; // app基础信息
    const KEY_AD_IDS = 'ad_ids'; // 广告信息
    const KEY_HELP = 'help'; // 帮助信息
    const KEY_QUERY_CONF = 'query_conf'; // 名称检测配置信息
    const KEY_TRIAL_CNT = 'trial_cnt'; // type1试用次数
    const KEY_TRIAL_CNT_APPLET = 'trial_cnt_applet'; // type2试用次数
    const WX_TICKET = 'wx_ticket'; // key-微信票据
    const WX_ACCESS_TOKEN = 'wx_access_token'; // key-微信token
    const KEY_QUERY_NUM = 'query_num'; // 今日免费查询次数
    const KEY_QUERY_USER = 'query_user'; // 查询用户锁前缀
    const KEY_UPLOAD = 'upload'; // 上传视频、图片
    const KEY_REWARD_WATCH_CNT = 'reward_watch_cnt'; // 激励广告观看次数
    const EXPIRE = 86400;

    const KEY_MCJC_ACCESS_TOKEN = 'mcjc_access_token'; // 名称检测access_token

    /**
     * 获取key值
     * @param string $key 键名称
     * @return string|boolean 返回键对应的值，无键值时返回false
     */
    public static function getKey($key)
    {
        if (Yii::$app->redis->exists($key)) {
            return Yii::$app->redis->get($key);
        }
        return false;
    }

    /**
     * 设置key值
     *
     * @param string $key 键名称
     * @param string|array|object $value 对应键的值
     */
    public static function setKey($key, $value)
    {
        Yii::$app->redis->set($key, $value); // 设置key值
    }


     /**
      * 设置key值
      *
      * @param string $key 键名称
      * @param string|array|object $value 对应键的值
      * @param integer $expireSeconds 过期时间（单位秒）
     * @return mixed bool
     */
    public static function setKeyWithExpire($key, $value, $expireSeconds)
    {
        return Yii::$app->redis->setex($key, $expireSeconds, $value); // 设置key值
    }

    /**
     * 设置key值 过期时间
     *
     * @param string $key 键名称
     * @param integer $expireSeconds 过期时间（单位秒）
     */
    public static function setExpire($key, $expireSeconds)
    {
        Yii::$app->redis->expire($key, $expireSeconds); // 设置key值过期时间
    }

    /**
     * 删除指定的键值
     * @param string $key 键名称
     * @return integer
     */
    public static function delKey($key)
    {
        return Yii::$app->redis->del($key); //删除key
    }

    /**
     * 从一个较大的字符串中返回一个比特
     * @param   string $key 键名
     * @param   int $offset 偏移量
     * @return  int     the bit value (0 or 1)
     */
    public static function getBit($key, $offset)
    {
        return Yii::$app->redis->getBit($key, $offset);
    }

    /**
     * 改变字符串的一个位
     * @param   string $key
     * @param   int $offset
     * @param   bool|int $value bool or int (1 or 0)
     * @return  int     0 or 1, the value of the bit before it was set.
     */
    public static function setBit($key, $offset, $value)
    {
        return Yii::$app->redis->setBit($key, $offset, $value);
    }

    /**
     * 从一个较大的字符串中返回n个指定的比特
     * @param string $key
     * @param array $data
     * @return array
     */
    public static function getMutiBits($key, $data)
    {
        $model = Yii::$app->redis->multi();
        foreach ($data as $v) {
            $model->getBit($key, $v);
        }
        return $model->exec();
    }

    /**
     * 字符串值添加到列表的头部(左)。如果键不存在，创建列表。如果键存在且不是列表，则返回FALSE。
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function lPush($key, $value)
    {
        return Yii::$app->redis->lPush($key, $value);
    }

    /**
     * 返回存储在指定项中的列表的指定元素范围[开始，结束]。开始和停止被解释为索引：0第一个元素，1第二个元素…-最后一个元素-2倒数第二个…
     * @param string $key
     * @param integer $start
     * @param integer $end
     * @return mixed
     */
    public static function lRange($key, $start, $end)
    {
        return Yii::$app->redis->lRange($key, $start, $end);
    }

    /**
     * 移除列表的最后一个元素，返回值为移除的元素。
     * @param string $key
     * @return mixed
     */
    public static function rPop($key)
    {
        return Yii::$app->redis->rPop($key);
    }

    /**
     * 返回列表的长度
     * @param string $key
     * @return mixed
     */
    public static function lLen($key)
    {
        return Yii::$app->redis->lLen($key);
    }

    /**
     * 将键处存储的数字增加一。
     * @param string $key
     * @return mixed
     */
    public static function incr($key)
    {
        return Yii::$app->redis->incr($key);
    }

    /**
     * 将键处存储的数字减少一。
     * @param string $key
     * @return mixed
     */
    public static function decr($key)
    {
        return Yii::$app->redis->decr($key);
    }

    /**
     * 统计指定位区间上值为1的个数。
     * @param string $key
     * @return mixed
     */
    public static function bitCount($key)
    {
        return Yii::$app->redis->bitCount($key);
    }

    /**
     * 位元操作
     * @param string $retKey
     * @param array $keys
     * @param string $operation
     * @return mixed
     */
    public static function bitOp($retKey, $keys, $operation = 'OR')
    {
        return Yii::$app->redis->bitOp($operation, $retKey, ...$keys);
    }

    /**
     * 从key中获取一个值。如果哈希表不存在，或者key不存在，则返回FALSE。
     *
     * @param string $key
     * @param string $hashKey
     */
    public static function hGet($key, $hashKey)
    {
        return Yii::$app->redis->hGet($key, $hashKey);
    }

    /**
     * 向key中存储的散列添加值
     *
     * @paramv string $key
     * @param string $hashKey
     * @param mixed $value
     * @return mixed
     */
    public static function hSet($key, $hashKey, $value)
    {
        return Yii::$app->redis->hSet($key, $hashKey, $value);
    }

    /**
     *  填充整个哈希。非字符串值通过使用标准(字符串)转换为字符串。NULL值存储为空字符串
     * @param string $key
     * @param array $hashKeys
     * @return mixed
     */
    public static function hMset($key, $hashKeys)
    {
        return Yii::$app->redis->hMset($key, $hashKeys);
    }

    /**
     * 根据给定的数量增加散列字段的浮点值
     *
     * @param $key
     * @param $field
     * @param $increment
     */
    public static function hIncrByFloat($key, $field, $increment)
    {
        Yii::$app->redis->hIncrByFloat($key, $field, $increment);
    }

    /**
     * 将成员的值从散列中增加一个给定的值
     *
     * @param $key
     * @param $hashKey
     * @param $value
     */
    public static function hIncrBy($key, $hashKey, $value)
    {
        Yii::$app->redis->hIncrBy($key, $hashKey, $value);
    }

    /**
     * 验证指定的成员是否存在于key中。
     *
     * @param $key
     * @param $hashKey
     * @return boolean 如果成员存在于哈希表中，返回TRUE，否则返回FALSE
     */
    public static function hExists($key, $hashKey)
    {
        return Yii::$app->redis->hExists($key, $hashKey);
    }

    /**
     * 将值添加到存储在键处的设置值。如果该值已在集合中，则返回false。
     * @param string $key
     * @param mixed ...$value
     * @return mixed
     */
    public static function sAdd($key, ...$value)
    {
        return Yii::$app->redis->sAdd($key, ...$value);
    }

    /**
     * 返回由键标识的集的基数。
     * @param $key
     * @return mixed
     */
    public static function sCard($key)
    {
        return Yii::$app->redis->sCard($key);
    }

    /**
     * 字符串值添加到列表的头部(左)。如果键不存在，创建列表。如果键存在且不是列表，则返回FALSE。
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function lPushMulti($key, ...$value)
    {
        return Yii::$app->redis->lPush($key, ...$value);
    }

    /**
     * 获取key过期时间
     *
     * @param $key
     * @return mixed
     */
    public static function ttl($key)
    {
        return Yii::$app->redis->ttl($key);
    }

    /**
     * 添加指定的成员，并将给定的分数添加到存储在key中的已排序集
     *
     * @param $key
     * @param $score
     * @param $value
     */
    public static function zAdd($key, $score, $value)
    {
        Yii::$app->redis->zAdd($key, $score, $value);
    }

    /**
     * 将一个成员的分数从一个给定的值中递增。
     * $key 或者 $member 不存在时，在递增时默认成员的原分数为0
     *
     * @param string $key 集合键值
     * @param float $value 分数值
     * @param mixed $member 成员
     */
    public static function zIncrBy($key, $value, $member)
    {
        Yii::$app->redis->zIncrBy($key, $value, $member);
    }

    /**
     * 返回存储在指定键上的已排序集的元素，这些元素在范围[开始，结束]中有分数。在开始或结束前添加一个圆括号将其排除在范围之外。
     * 当开始和结束参数交换时，zRevRangeByScore以相反的顺序返回相同的项
     *
     * @param string $key 有序集合键值
     * @param int $start 开始下标
     * @param int $end 结束下标
     * @param boolean $withscore 是否显示分数
     * @return mixed 返回范围内的排行信息
     */
    public static function zRevRange($key, $start, $end, $withscore = null)
    {
        return Yii::$app->redis->zRevRange($key, $start, $end, $withscore);
    }
}