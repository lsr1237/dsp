<?php
namespace backend\models;

use common\bases\CommonModel;

class AuthItemModel extends CommonModel 
{
    const TYPE_ROLE = 1;//角色
    const TYPE_PERMISSION = 2;//权限

    public static function findAllByCond($cond)
    {
        return AuthItem::find()->where($cond)->all();
    }

    public static function getList($offset, $limit, $name, $type)
    {
        $model = AuthItem::find();
        if ($type) {
            $model->andWhere(['type' => $type]);
        }
        if ($name) {
            $model->andWhere(['LIKE', 'name' , trim($name)]);
        }
        return [
            'count' => $model->count(),
            'list' => $model->offset($offset)->limit($limit)->all()
        ];
    }

    public static function findOneByCond($cond)
    {
        return AuthItem::find()->where($cond)->one();
    }

    public static function add($data)
    {
        $model = new AuthItem();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return true;
        }
        return false;
    }

    public static function update($name, $data)
    {
        $model = AuthItem::findOne(['name' => $name]);
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return true;
        }
        return false;
    }

    public static function del($name)
    {
        return AuthItem::deleteAll(['name' => $name]);
    }

    public static function getOneAsArrayByName($name)
    {
        return AuthItem::find()->where(['name' => trim($name)])->asArray()->one();
    }
}
