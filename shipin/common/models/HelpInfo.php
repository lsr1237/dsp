<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "help_info".
 *
 * @property int $id
 * @property string $question 问题
 * @property string $answer 回答
 * @property int $wx_id 所属小程序id
 * @property int $type 类型 1尺寸剪裁 2时长剪裁 3加水印 4获取音频 5倒放 6修改封面 7变速 8压缩 9修改md5 10去水印 11去除音频 12视频解析
 * @property int $sort 排序
 * @property int $state 状态 1显示 2隐藏
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class HelpInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'help_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['wx_id', 'type', 'sort', 'state'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['question'], 'string', 'max' => 300],
            [['answer'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question' => 'Question',
            'answer' => 'Answer',
            'wx_id' => 'Wx ID',
            'type' => 'Type',
            'sort' => 'Sort',
            'state' => 'State',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
