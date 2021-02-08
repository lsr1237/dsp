<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "app_intros".
 *
 * @property string $version 版本号
 * @property string $intro 更新说明
 * @property string $restricted_platform 平台限制，只用于该平台的说明
 *
 * @property AppInfo[] $appInfos
 */
class AppIntros extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_intros';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['version'], 'required'],
            [['version_code'], 'integer'],
            [['intro'], 'string'],
            [['version'], 'string', 'max' => 12],
            [['restricted_platform'], 'string', 'max' => 20],
            [['version'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'version' => 'Version',
            'version_code' => 'Version Code',
            'intro' => 'Intro',
            'restricted_platform' => 'Restricted Platform',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAppInfos()
    {
        return $this->hasMany(AppInfo::className(), ['latest_version' => 'version']);
    }
}
