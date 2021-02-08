<?php
namespace common\models;


use common\bases\CommonModel;

class QueryConfModel extends CommonModel
{
    /**
     * 添加
     * @param array $data
     * @return bool|QueryConf
     */
    public static function add($data)
    {
        $model = new QueryConf();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        }
        return false;
    }

    /**
     * 更新
     * @param array $cond 查找条件
     * @param array $data 更新参数
     * @return bool
     */
    public static function updateByCond($cond, $data)
    {
        $model = QueryConf::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据条件查找
     * @param array $cond
     * @return array|null|\yii\db\ActiveRecord|QueryConf
     */
    public static function findOneByCond($cond)
    {
        return QueryConf::find()->where($cond)->one();
    }

    /**
     * 获取app基本信息
     * @return array|null|\yii\db\ActiveRecord|QueryConf
     */
    public static function getConf()
    {
        return QueryConf::find()->one();
    }
}