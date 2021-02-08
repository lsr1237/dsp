<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "app_basic".
 *
 * @property int $id
 * @property string $config 配置信息
 * @property int $wx_id 小程序id
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $id_config 流量主id配置
 */
class AppBasic extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_basic';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['wx_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['config', 'id_config'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'config' => 'Config',
            'wx_id' => 'Wx ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'id_config' => 'Id Config',
        ];
    }
}
