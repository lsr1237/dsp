<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/11/11
 * Time: 14:53
 */

namespace common\models;

use Yii;

class MonitorLogModel
{
    const LEN_LIMIT = 50; // 名字长度限制50
    const STATE_OPEN = 1; // 开启
    const STATE_CLOSE = 2; // 关闭

    /**
     * 添加记录
     * @param $data
     * @return bool|MonitorLog
     */
    public static function add($data)
    {
        $model = new MonitorLog();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        } else {
            Yii::error($model->getErrors());
            return false;
        }
    }

    /**
     * 更新记录
     * @param $cond
     * @param $data
     * @return bool|MonitorLog|null
     */
    public static function updateByCond($cond, $data)
    {
        $model = MonitorLog::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return $model;
            }
        }
        return false;
    }

    /**
     * 根据条件查询一条记录
     * @param $cond
     * @return MonitorLog|null
     */
    public static function getOne($cond)
    {
        return MonitorLog::findOne($cond);
    }

    /**
     * 根据条件查询所有记录
     * @param $cond
     * @return MonitorLog[]
     */
    public static function getAll($cond)
    {
        return MonitorLog::findAll($cond);
    }

    /**
     * 获取所有开启记录
     * @return array|\yii\db\ActiveRecord[]|MonitorLog[]
     */
    public static function getAllActive()
    {
        return MonitorLog::find()->with('user')->where(['state' => self::STATE_OPEN])->all();

    }
}