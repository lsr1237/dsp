<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "notice".
 *
 * @property int $id
 * @property string $title 公告标题
 * @property int $state 状态：1：显示 2：不显示
 * @property int $type 类型：1：公告 2：banner
 * @property string $image 图片
 * @property string $url 链接
 * @property int $sort 排序
 * @property string|null $content  内容
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $app_id 小程序id
 * @property int $is_jump 是否跳转小程序 1跳转 2不跳转
 * @property int $wx_id 所属小程序id
 */
class Notice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['state', 'type', 'sort', 'is_jump', 'wx_id'], 'integer'],
            [['content'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['image', 'url'], 'string', 'max' => 100],
            [['app_id'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'state' => 'State',
            'type' => 'Type',
            'image' => 'Image',
            'url' => 'Url',
            'sort' => 'Sort',
            'content' => 'Content',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'app_id' => 'App ID',
            'is_jump' => 'Is Jump',
            'wx_id' => 'Wx ID',
        ];
    }
}
