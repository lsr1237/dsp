<?php
/** 
 * 模型基类
 * BaseModel.php
 * @author     addition
 */ 
namespace common\bases;
use Yii;
use yii\base\Model;

class CommonModel extends Model {
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILURE = 'FAILURE';
    /**
     * 判断值是否在二维数组里
     * @param $value
     * @param $array
     * @param string $key
     * @return bool
     */
    public static function deepInArray($value, $array, $key = 'id')
    {
        foreach ($array as $item) {
            if ($value == $item[$key]) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断值是否在二维数组里并返回所有序号
     */
    public static function deepSearchArray($values, $arrays, $onlyFirst = false)
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        $keys = [];
        foreach ($values as $value) {
            foreach ($arrays as $key => $array) {
                if (in_array($value, $array)) {
                    $keys[] = $value;
                    if ($onlyFirst) {
                        break;
                    }
                }
            }
        }
        return $keys;
    }
}