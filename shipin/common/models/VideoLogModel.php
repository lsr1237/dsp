<?php

namespace common\models;


use common\bases\CommonModel;

class VideoLogModel extends CommonModel
{
    const STATE_SUCCESS = 1; // 成功
    const STATE_ERROR = 2;   // 失败
    const STATE_PAID = 3; // 已付款

    const TYPE_SIZE_CUT = 1; // 尺寸剪裁
    const TYPE_DURATION_CUT = 2; // 时长剪裁
    const TYPE_ADD_WATERMARK = 3; // 添加水印
    const TYPE_GET_AUDIO = 4; // 获取音频
    const TYPE_REVERSE = 5; // 视频倒放
    const TYPE_MODIFY_COVER = 6; // 修改视频封面
    const TYPE_SPEED = 7; // 视频变速
    const TYPE_COMPRESS = 8; // 视频压缩
    const TYPE_MD5 = 9; // 修改MD5
    const TYPE_RM_WATERMARK = 10; // 去水印
    const TYPE_DEL_AUDIO = 11; // 去除音频
    const TYPE_ANALYSIS = 12; // 视频解析


    /**
     * 添加
     * @param array $data
     * @return bool|VideoLog
     */
    public static function add($data)
    {
        $model = new VideoLog();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        }
        return false;
    }

    /**
     * 获取列表
     * @param $limit
     * @param $offset
     * @param int $userId
     * @return array
     */
    public static function getList($limit, $offset, $userId = 0)
    {
        $model = VideoLog::find();
        if (!empty($userId)) {
            $model->where(['user_id' => $userId]);
        }
        $model->andWhere(['state' => [self::STATE_SUCCESS, self::STATE_PAID]]);
        return [
            'count' => (int)$model->count('id'),
            'list' => $model->limit($limit)->offset($offset)->orderBy(['id' => SORT_DESC])->all()
        ];
    }

    /**
     * 获取记录
     * @param $cond
     * @return array|null|\yii\db\ActiveRecord|VideoLog
     */
    public static function findOneByCond($cond)
    {
        return VideoLog::find()->where($cond)->one();
    }

    /**
     * 根据条件修改
     * @param $cond
     * @param $data
     * @return bool
     */
    public static function updateByCond($cond, $data)
    {
        $model = VideoLog::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取解析记录
     * @param $limit
     * @param $offset
     * @param $number
     * @param $beginAt
     * @param $endAt
     * @param array $orderBy
     * @return array
     */
    public static function getAnalysisLog($limit, $offset, $number, $beginAt, $endAt, $orderBy = ['video_log.id' => SORT_DESC])
    {
        $model = VideoLog::find()->where(['type' => self::TYPE_ANALYSIS]);
        if ($number) {
            $model->joinWith('user');
            $model->andWhere(['user.number' => $number]);
        } else {
            $model->with('user');
        }
        if ($beginAt != '') {
            $beginAt = date('Y-m-d 00:00:00', (int)$beginAt); // 时间戳转字符串
            $model->andWhere(['>=', 'video_log.created_at', $beginAt]); // 起始时间
        }
        if ($endAt != '') {
            $endAt = date('Y-m-d 23:59:59', (int)$endAt); // 时间戳转字符串
            $model->andWhere(['<=', 'video_log.created_at', $endAt]); // 截止时间
        }
        return [
            'count' => (int)$model->count('video_log.id'),
            'list' => $model->offset($offset)->limit($limit)->orderBy($orderBy)->all()
        ];
    }
}