<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/11/3
 * Time: 17:38
 */

namespace common\models;

use Yii;

class QueryUserModel
{
    /**
     * 添加用户
     * @param $data
     * @return bool|QueryUser
     */
    public static function add($data)
    {
        $model = new QueryUser();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        } else {
            Yii::error($model->getErrors());
            return false;
        }
    }

    /**
     * 更新用户
     * @param $cond
     * @param $data
     * @return bool|QueryUser|null
     */
    public static function updateByCond($cond, $data)
    {
        $model = QueryUser::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return $model;
            }
        }
        return false;
    }


    /**
     * 用户管理-禁用-按ID查询用户信息
     * @param $userId
     * @return QueryUser|null
     */
    public static function findUserById($userId)
    {
        return QueryUser::findOne(['id' => $userId]);
    }

    /**
     * @param $cond
     * @return array|\yii\db\ActiveRecord|null
     */
    public static function findOneByCond($cond)
    {
        return QueryUser::find()->where($cond)->one();
    }
}