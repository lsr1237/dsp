<?php

namespace common\models;


use common\bases\CommonModel;

class ErrLogModel extends CommonModel
{
    /**
     * 添加
     * @param $data
     * @return bool|ErrLog
     */
    public static function add($data)
    {
        $model = new ErrLog();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        }
        return false;
    }

    /**
     * 更新记录
     * @param $data
     * @param $cond
     * @return bool
     */
    public static function update($cond, $data)
    {
        $model = ErrLog::updateAll($data, $cond);
        if ($model) {
            return true;
        }
        return false;
    }

    /**
     * @param $limit
     * @param $offset
     * @param $number
     * @param $beginAt
     * @param $endAt
     * @param array $orderBy
     * @param $isStatistics
     * @return array
     */
    public static function getList($limit, $offset, $number, $beginAt, $endAt, $orderBy = ['err_log.id' => SORT_DESC], $isStatistics = false)
    {
        $model = ErrLog::find();
        if ($isStatistics) {
            $model->groupBy(['host']);
            $model->select(['host, count(err_log.id) as cnt']);
            return  $model->offset($offset)->limit($limit)->orderBy($orderBy)->asArray()->all();
        }
        if ($number) {
            $model->joinWith('user');
            $model->andWhere(['user.number' => $number]);
        } else {
            $model->with('user');
        }
        if ($beginAt != '') {
            $beginAt = date('Y-m-d 00:00:00', (int)$beginAt); // 时间戳转字符串
            $model->andWhere(['>=', 'err_log.created_at', $beginAt]); // 起始时间
        }
        if ($endAt != '') {
            $endAt = date('Y-m-d 23:59:59', (int)$endAt); // 时间戳转字符串
            $model->andWhere(['<=', 'err_log.created_at', $endAt]); // 截止时间
        }
        return [
            'count' => (int)$model->count('err_log.id'),
            'list' => $model->offset($offset)->limit($limit)->orderBy($orderBy)->all()
        ];
    }
}