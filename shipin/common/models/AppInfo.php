<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "app_info".
 *
 * @property string $platform 平台（IOS/ANDROID）
 * @property string $latest_version 最新版本号
 * @property string $app_url app下载地址
 * @property string $link_url 跳转链接下载地址
 * @property string $min_force_update_version 强制升级的最小版本号
 * @property string $max_force_update_version 强制升级的最大版本号
 * @property string $min_prompt_update_version 提示升级的最小版本号
 * @property string $max_prompt_update_version 提示升级的最大版本号
 *
 * @property AppIntros $latestVersion
 */
class AppInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform'], 'required'],
            [['platform'], 'string', 'max' => 20],
            [['latest_version', 'min_force_update_version', 'max_force_update_version', 'min_prompt_update_version', 'max_prompt_update_version'], 'string', 'max' => 12],
            [['app_url', 'link_url'], 'string', 'max' => 200],
            [['platform'], 'unique'],
            [['latest_version'], 'exist', 'skipOnError' => true, 'targetClass' => AppIntros::className(), 'targetAttribute' => ['latest_version' => 'version']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'platform' => 'Platform',
            'latest_version' => 'Latest Version',
            'app_url' => 'App Url',
            'link_url' => 'Link Url',
            'min_force_update_version' => 'Min Force Update Version',
            'max_force_update_version' => 'Max Force Update Version',
            'min_prompt_update_version' => 'Min Prompt Update Version',
            'max_prompt_update_version' => 'Max Prompt Update Version',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLatestVersion()
    {
        return $this->hasOne(AppIntros::className(), ['version' => 'latest_version']);
    }
}
