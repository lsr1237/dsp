<?php

namespace common\models;


use common\bases\CommonModel;

class NumLogModel extends CommonModel
{
    const TYPE_INVITE = 1; // 邀请好友
    const TYPE_BUY = 2; // 购买次数
    const TYPE_AD = 3; // 观看广告
    const TYPE_DOWNLOAD = 4; //下载
    const TYPE_RM_WATERMARK = 5; // 视频解析
    const TYPE_REGISTER = 6; // 注册奖励

    const TYPE_MAP = [
        self::TYPE_INVITE => '邀请新人注册',
        self::TYPE_BUY => '购买次数',
        self::TYPE_AD => '观看广告',
        self::TYPE_DOWNLOAD => '视频下载',
        self::TYPE_RM_WATERMARK => '视频解析',
        self::TYPE_REGISTER => '注册奖励',
    ];

    const TYPE_MAP_NEGATIVE = [
        self::TYPE_DOWNLOAD,
        self::TYPE_RM_WATERMARK,
    ];

    /**
     * 添加
     * @param array $data
     * @return bool
     */
    public static function add($data)
    {
        $model = new NumLog();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return true;
        }
        return false;
    }

    /**
     * 更新
     * @param array $cond 查找条件
     * @param array $data 更新参数
     * @return bool
     */
    public static function updateByCond($cond, $data)
    {
        $model = NumLog::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 条件查找
     * @param array $cond 条件
     * @return null|static|NumLog
     */
    public static function findOneByCond($cond)
    {
        return NumLog::findOne($cond);
    }

    /**
     * 获取记录
     * @param $offset
     * @param $limit
     * @param $userId
     * @param $type
     * @param array $orderBy
     * @return array | ['count' => int, 'list' => NumLog[]]
     */
    public static function getList($offset, $limit, $userId, $type, $orderBy = ['id' =>SORT_DESC])
    {
        $model = NumLog::find();
        if ($userId) {
            $model->andWhere(['user_id' => $userId]);
        }
        if ($type) {
            $model->andWhere(['type' => $type]);
        }
        return [
            'count' => (int)$model->count('id'),
            'list' => $model->limit($limit)->offset($offset)->orderBy($orderBy)->all()
        ];
    }

}