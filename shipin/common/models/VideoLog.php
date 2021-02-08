<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "video_log".
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property string|null $ori_path 原视频路径/解析封面地址
 * @property string|null $save_path 保存视频路径/解析视频地址
 * @property string|null $ori_cover 原始视频封面
 * @property string|null $ori_video 原始视频
 * @property int $type 类型 1尺寸剪裁 2时长剪裁 3加水印 4获取音频 5倒放 6修改封面 7变速 8压缩 9修改md5 10去水印
 * @property int $state 状态 1-成功 2-失败 3-成功且支付
 * @property string|null $input_txt 视频解析输入内容
 * @property string|null $memo 备注
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class VideoLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'video_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'state'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['ori_path', 'ori_cover'], 'string', 'max' => 1200],
            [['save_path', 'ori_video'], 'string', 'max' => 1500],
            [['input_txt'], 'string', 'max' => 500],
            [['memo'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'ori_path' => 'Ori Path',
            'save_path' => 'Save Path',
            'ori_cover' => 'Ori Cover',
            'ori_video' => 'Ori Video',
            'type' => 'Type',
            'state' => 'State',
            'input_txt' => 'Input Txt',
            'memo' => 'Memo',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
