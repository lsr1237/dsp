<?php

namespace common\extend\redis;

use Redis;
use Yii;
use yii\base\NotSupportedException;
use yii\base\Component;
use yii\helpers\Json;

/**
 * @method mixed get($key)
 * @method bool set($key, $value, $timeout = 0)
 * @method bool exists($key)
 * @method int delete($key1, $key2 = null, $key3 = null)
 * @method int sAdd($key, $value1, $value2 = null, $valueN = null)
 * @method int sRem($key, $member1, $member2 = null, $memberN = null)
 * @method bool sIsMember($key, $value)
 * @method int zAdd($key, $score1, $value1, $score2 = null, $value2 = null, $scoreN = null, $valueN = null)
 * @method array zRange($key, $start, $end, $withscores = null)
 * @method array zRevRange($key, $start, $end, $withscore = null)
 * @method int zRem($key, $member1, $member2 = null, $memberN = null)
 * @method bool expire($key, $ttl)
 * @method bool expireAt($key, $timestamp)
 */
class RedisClient extends Component
{
    public $hostname;
    public $port;
    public $database;
    public $password;
    public $enable_stats = false;

    private $_redis;
    private $notSupporteds = ['open', 'popen', 'connect', 'pconnect', 'select'];

    /**
     * 支持phpredis各种操作
     *
     * @param string $name redis命令
     * @param array $params redis命令参数
     * @return mixed 返回类型与phpredis相应命令返回类型一致
     */
    public function __call($name, $params = [])
    {
        if(in_array($name, $this->notSupporteds)) {
            throw new NotSupportedException($name . ' Not Supported');
        }
        return call_user_func_array([$this->getRedis(), $name], $params);
    }

    /**
     * 获取$key对应的缓存，如果取不到，则调用$source()来获取，并将结果缓存
     *
     * @param int $key 缓存key
     * @param callable $source 数据源
     * @param int $duration [optional] 缓存时间，不传此参数则缓存不过期
     * @param bool $json [optional] 是否采用Json encode/decode
     * @return mixed 返回类型与$source()一致
     */
    public function cache($key, callable $source, $duration = 0, $json = true)
    {
        $redis = $this->getRedis();
        $result = $redis->get($key);
        $statsExpireAt = strtotime(date('Ymd')) + 24 * 60 * 60; // 当天过期
        if ($result !== false) {
            if($this->enable_stats) {
                $hitsKey = 'Hits:' . $key;
                $redis->incr($hitsKey);
                $redis->expireAt($hitsKey, $statsExpireAt);
            }
            Yii::info(sprintf('Hit cache %s.', $key));
            $returnData = $json ? Json::decode($result) : unserialize($result);
            return $returnData;
        }
        if($this->enable_stats) {
            $missesKey = 'Misses:' . $key;
            $redis->incr($missesKey);
            $redis->expireAt($missesKey, $statsExpireAt);
        }
        $result = $source();
        if ($result) {
            $cacheData = $json ? Json::encode($result) : serialize($result);
            if($duration > 0) {
                $redis->set($key, $cacheData, $duration);
            } else {
                $redis->set($key, $cacheData);
            }
        }

        return $result;
    }

    private function getRedis()
    {
        if (!$this->_redis) {
            $this->_redis = new Redis();
            $this->_redis->pconnect($this->hostname, $this->port);
            if (!empty($this->password)) {
                $this->_redis->auth($this->password);
            }
            $this->_redis->select($this->database);
            $this->_redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }
        return $this->_redis;
    }
}
