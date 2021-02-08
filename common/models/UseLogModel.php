<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/25
 * Time: 10:26
 */

namespace common\models;


use common\bases\CommonModel;

class UseLogModel extends CommonModel
{
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
    const TYPE_MAP = [
        self::TYPE_SIZE_CUT,
        self::TYPE_DURATION_CUT,
        self::TYPE_ADD_WATERMARK,
        self::TYPE_GET_AUDIO,
        self::TYPE_REVERSE,
        self::TYPE_MODIFY_COVER,
        self::TYPE_SPEED,
        self::TYPE_COMPRESS,
        self::TYPE_MD5,
        self::TYPE_RM_WATERMARK,
    ];

    /**
     * 添加记录
     * @param $data
     * @return bool|string
     */
    public static function add($data)
    {
        $model = new UseLog();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return $model->id;
        }
        return false;
    }

    /**
     * 编辑记录
     * @param $id
     * @param $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $model = UseLog::findOne(['id' => $id]);
        if ($model) {
            $model->setAttributes($data);
            $res = $model->save();
            if ($res) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取记录列表
     * @param $limit
     * @param $offset
     * @param int $userId
     * @return array
     */
    public static function getList($limit, $offset, $userId = 0)
    {
        $model = UseLog::find();
        if (!empty($userId)) {
            $model->andWhere(['user_id' => $userId]);
        }
        return [
            'count' => (int)$model->count('id'),
            'list' => $model->limit($limit)->offset($offset)->orderBy(['id' => SORT_DESC])->all()
        ];
    }
}