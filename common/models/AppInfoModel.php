<?php

namespace common\models;

use common\bases\CommonModel;

class AppInfoModel extends CommonModel
{
    const PLATFORM_IOS = 'IOS'; // IOS
    const PLATFORM_ANDROID = 'ANDROID'; // android

    /**
     * 添加app信息
     * @param array $data 参数信息
     * @return bool|string
     */
    public static function add($data)
    {
        $model = new AppInfo();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return $model->platform;
        }
        return false;
    }

    /**
     * 根据平台标志修改或创建app信息记录
     * @param array $data 参数信息
     * @return bool true|false
     */
    public static function update($data)
    {
        $model = AppInfo::findOne(['platform' => $data['platform']]);

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
     * 根据平台标志查找app信息
     * @param string $platform IOS|ANDROID
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findOneByPlatform($platform)
    {
        return AppInfo::find()->with('latestVersion')->where(['platform' => $platform])->one();
    }
}