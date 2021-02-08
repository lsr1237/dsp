<?php

namespace common\extend;

use common\models\MobileLog;
use Mpdf\Mpdf;
use yii\helpers\Json;

class Tool
{
    const LOWER_RMB = 1; // 小写人民币
    const UPPER_RMB = 2; // 大写人民币

    // 取整方法
    const WAY_UP = 1; // 向上
    const WAY_DOWN = 2; // 向下

    /**
     * 切分字符串
     * @date 2017-09-0
     * @auther: addition
     * @param unknowtype
     * @return return_type
     */
    public static function expReturnKey($value, $key = -1, $delimiter = ':::', $ifnull = '')
    {
        $v = explode($delimiter, $value);
        if ($key == -1) {
            return $v;
        } else {
            if (!isset($v[$key])) {
                return $ifnull;
            } else {
                return $v[$key];
            }
        }
    }

    /**
     * 生成字母数字随机组合数
     * 例如随机生成 5 位 字母和数字组合 getRandomString(2);
     * @auther: addition
     * @param $len
     * @param null $chars
     * @return string
     */
    public static function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    public static function getRandomStringNoNum($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * 生成随机字母
     * @param $len
     * @param null $chars
     * @return string
     */
    public static function getRandomNum($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "0123456789";
        }

        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * 过滤emoji
     * @param $str
     * @return mixed
     */
    public static function filterEmoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

        return $str;
    }

    // 判断字符串 $str 是否以 $needle 开头
    public static function startWith($str, $needle)
    {
        return strpos($str, $needle) === 0;
    }

    /**
     * 获取当前时间至当日24点的秒数
     * @return integer 秒数
     */
    public static function getSecondsAwayFrom24Oclock()
    {
        $targetTime = strtotime(date('Y-m-d 23:59:59'));
        return $targetTime - time();
    }

    /**
     * 图片文件转base64编码
     * @param mixed $imageFile 图片文件
     * @return string
     */
    public static function base64EncodeImage($imageFile)
    {
        $base64Image = '';
        $imageInfo = getimagesize($imageFile);
        $imageData = fread(fopen($imageFile, 'r'), filesize($imageFile));
        $base64Image = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode($imageData));
        return $base64Image;
    }

    /**
     * 根据身份证号获取用户年龄（周岁）
     * @param string $idNo 身份证号
     * @return int 年龄（周岁）
     */
    public static function getAgeByIdNo($idNo)
    {
        $age = date('Y') - substr($idNo, 6, 4) + (date('md') >= substr($idNo, 10, 4) ? 1 : 0);
        return (integer)$age;
    }

    /**
     * 身份证格式验证
     * @param string $idNo 身份证号
     * @return bool 返回是否认证通过
     */
    public static function isIdCardNo($idNo)
    {
        $id = strtoupper($idNo);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arrSplit = array();
        if (!preg_match($regx, $id)) {
            return false;
        }
        if (15 == strlen($id)) { // 检查15位
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            @preg_match($regx, $id, $arrSplit);
            // 检查生日日期是否正确
            $dtmBirth = "19" . $arrSplit[2] . '/' . $arrSplit[3] . '/' . $arrSplit[4];
            if (!strtotime($dtmBirth)) {
                return false;
            } else {
                return true;
            }
        } else { // 检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arrSplit);
            $dtmBirth = $arrSplit[2] . '/' . $arrSplit[3] . '/' . $arrSplit[4];
            if (!strtotime($dtmBirth)) //检查生日日期是否正确
            {
                return false;
            } else {
                // 检验18位身份证的校验码是否正确。
                // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arrInt = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arrCh = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b = (int)$id{$i};
                    $w = $arrInt[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $valNum = $arrCh[$n];
                if ($valNum != substr($id, 17, 1)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    /**
     * 二维数组 根据某一键名的值不能重复，删除重复项
     * @param array $arr 二维数组
     * @param string $key 指定键值
     * @return mixed 返回过滤完成后的数组
     */
    public static function assocUnique($arr, $key)
    {
        $tmpArr = [];
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmpArr)) { // 搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmpArr[] = $v[$key];
            }
        }
        sort($arr); // sort函数对数组进行排序
        return $arr;
    }

    /**
     * 两个字符串匹配
     * @param string $orlStr 原字符串
     * @param string $targetStr 目标字符串
     * @return bool
     */
    public static function matchStr($orlStr, $targetStr)
    {
        $orlStr = strtoupper($orlStr);
        $targetStr = strtoupper($targetStr);
        $length = mb_strlen($targetStr);
        $ret = true;
        for ($i = 0; $i < $length; $i++) {
            $a = mb_substr($orlStr, $i, 1);
            $b = mb_substr($targetStr, $i, 1);
            if ($b == '*') {
                continue;
            }
            if ($a != $b) {
                $ret = false;
                break;
            }
        }
        return $ret;
    }

    /**
     * 数据存入数据库前压缩
     * @param string $data
     * @return string
     */
    public static function gzCompress($data)
    {
        return $data ? base64_encode(gzcompress($data)) : '';
    }

    /**
     * 数据读取是解压缩
     * @param string $data
     * @return string
     */
    public static function gzUnCompress($data)
    {
        return $data ? gzuncompress(base64_decode($data)) : '';
    }

    /**
     * 字符串拼接n位随机值
     * @param $str
     * @param int $len
     * @return string
     */
    public static function strEncryption($str, $len = 6)
    {
        return $str . self::getRandomString($len);
    }

    /**
     * 截取$len位后的字符串
     * @param $str
     * @param int $len
     * @return bool|string
     */
    public static function strDecrypt($str, $len = -6)
    {
        return mb_substr($str, 0, $len);
    }

    /**
     * 获取百分比
     * @param int $molecule 分子
     * @param int $denominator 分母
     * @param bool $limit 是否限制100%
     * @param string $postfix 后缀
     * @return float|int
     */
    public static function getPercentage($molecule, $denominator, $limit = true, $postfix = '')
    {
        if ($denominator > 0) {
            $percentage = round($molecule / $denominator * 100, 2);
            if ($limit) {
                return sprintf('%s%s', $percentage > 100 ? 100 : $percentage, $postfix);
            } else {
                return sprintf('%s%s', $percentage, $postfix);
            }
        } else {
            return sprintf('0%s', $postfix);
        }
    }

    /**
     * 将金额转为中文
     * @param $num
     * @param $type
     * @return string
     */
    public static function numToRmb($num, $type = 1)
    {
        if ($type == self::LOWER_RMB) {
            $rmbNum = "零一二三四五六七八九";
            $rmbUnit = "分角元十百千万十百千亿";
        } elseif ($type == self::UPPER_RMB) {
            $rmbNum = "零壹贰叁肆伍陆柒捌玖";
            $rmbUnit = "分角元拾佰仟万拾佰仟亿";
        }
        $num = floatval($num) * 100;
        $numArr = str_split(trim($num)); // 转为数组
        $numArr = array_reverse($numArr); // 反转数组
        $retArr = [];
        foreach ($numArr as $key => $val) {
            $num = substr($rmbNum, $val * 3, 3);
            $unit = substr($rmbUnit, $key * 3, 3);
            array_unshift($retArr, sprintf('%s%s', $num, $unit));
            unset($num);
            unset($unit);
        }
        $retStr = implode('', $retArr);
        return $retStr;
    }

    /**
     * 获取毫秒时间戳
     * @return float
     */
    public static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 校验密码
     * 规则：必须同时包含6-15位：数字、大写字母、小写字母、特殊符号组成
     * @param $password
     * @return bool
     */
    public static function checkPassword($password)
    {
        if (preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*/', $password)) {
            return false;
        }
        return true;
    }

    /**
     * 判断关联数组键值是否为空
     * @param $array
     * @return string
     */
    public static function checkEmpty($array)
    {
        if (!is_array($array) || empty($array)) {
            return '参数为空或错误！';
        }
        foreach ($array as $key => $value) {
            if (is_float($value) && $value == 0) {
                return sprintf('%s为0！', $key);
            } elseif (empty($value)) {
                return sprintf('%s为空！', $key);
            }
        }
        return '';
    }

    /**
     * 校验手机号格式
     * @param $mobile
     * @return bool
     */
    public static function checkMobile($mobile)
    {
        if (preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
            return true;
        }
        return false;
    }

    /**
     * XML 转 数组
     * @param $xml
     * @return mixed
     */
    public static function xmlToArr($xml)
    {
        $obj = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        return json_decode(json_encode($obj), true);
    }

    /**
     * 获取当前日期距离指定日期间隔的天数
     * @param string $date 指定日期
     * @return float|int 天数
     */
    public static function getDaysAwayFromFixedDate($date)
    {
        $targetTime = strtotime($date);
        $difference = abs($targetTime - strtotime(date('Y-m-d')));
        return $difference / 86400;
    }

    /**
     * 取整小数
     * @param $num
     * @param int $digit 小数位数
     * @param int $way 取整方法 1向上 2向下
     * @return float|int
     */
    public static function roundNum($num, $digit, $way = self::WAY_UP)
    {
        $divisor = pow(10, $digit);
        if ($way == self::WAY_DOWN) {
            $num = floatval(floor($num * $divisor) / $divisor);
        } else {
            $num = floatval(ceil($num * $divisor) / $divisor);
        }
        return $num;
    }

    /**
     * 生成商户流水号（28位）
     * @param integer $userId 用户ID
     * @return string
     */
    public static function getOrderNo($userId)
    {
        return sprintf('%s%s%s', date('YmdHis'), str_pad($userId, 8, '0', STR_PAD_LEFT), Tool::getRandomNum(5));
    }

    /**
     * 字符串脱敏
     * @param $str
     * @param $startNum
     * @param $endNum
     * @return string
     */
    public static function desensitization($str, $startNum, $endNum)
    {
        $len = mb_strlen($str);
        if (($startNum + $endNum) >= $len) {
            return str_repeat('*', $len);
        }
        $replaceStr = str_repeat('*', $len - $startNum - $endNum);
        return mb_substr($str, 0, $startNum) . $replaceStr . mb_substr($str, -$endNum, $endNum);
    }

    /**
     * 获取比例
     * @param float $molecule 分子
     * @param float $denominator 分母
     * @param int $precision 保留小数位数
     * @return float|int
     */
    public static function getRate($molecule, $denominator, $precision = 4)
    {
        if ($denominator == 0) {
            return 0;
        }
        return round($molecule / $denominator, $precision) * 100;
    }
}