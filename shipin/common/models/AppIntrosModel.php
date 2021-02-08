<?php

namespace common\models;

use common\bases\CommonModel;

class AppIntrosModel extends CommonModel
{
    /**
     * 添加app更新历史记录
     * @param array $data 参数
     * @return bool|string 成功返回版本号，失败返回false
     */
    public static function add($data)
    {
        $model = new AppIntros();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return $model->version;
        }
        return false;
    }

    /**
     * 根据版本号更新app更新记录或者新增
     * @param array $data 参数信息
     * @return bool 成功返回true失败返回false
     */
    public static function update($data)
    {

        $model = AppIntros::findOne(['version' => $data['version']]);
        if ($model) {
            $model->setAttributes($data);
            $result = $model->save();
            if ($result) {
                return true;
            }
        } else {
            if (self::add($data)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据版本号查找更新记录
     * @param string $version 版本号
     * @return null|AppIntros
     */
    public static function findOneByVersion($version)
    {
        return AppIntros::findOne(['version' => $version]);
    }

    /**
     * 获取所有的版本信息
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAllVersion()
    {
        return AppIntros::find()->select('version')->all();
    }


}