<?php

namespace backend\models;


use common\bases\CommonModel;

class AuthItemChildModel extends CommonModel
{
    public static function findOneByCond($cond)
    {
        return AuthItemChild::find()->where($cond)->one();
    }

    public static function findAllByCond($cond)
    {
        return AuthItemChild::find()->where($cond)->all();
    }

    public static function add($data)
    {
        $model = new AuthItemChild();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return true;
        }
        return false;
    }
}