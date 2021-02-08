<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/18
 * Time: 9:27
 */

namespace common\models;


use common\bases\CommonModel;

class FeedbackModel extends CommonModel
{
    const CONTENT_LIMIT = 300; // 内容长度限制
    const INFO_LIMIT = 15; // 联系方式长度限制

    const STATE_UNDO = 1; // 状态 未处理
    const STATE_DO = 2; // 已处理

    const PLATFORM_IOS = 1;
    const PLATFORM_ANDROID = 2;
    const PLATFORM_APPLETS = 3;

    /**
     * 添加
     * @param $data
     * @return bool|Feedback
     */
    public static function add($data)
    {
        $model = new Feedback();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        }
        return false;
    }


    /**
     * 更新
     * @param $cond
     * @param $data
     * @return bool|Feedback|null
     */
    public static function updateByCond($cond, $data)
    {
        $model = Feedback::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return $model;
            }
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
        $model = Feedback::find();
        if (!empty($userId)) {
            $model->where(['user_id' => $userId]);
        }
        return [
            'count' => (int)$model->count('id'),
            'list' => $model->limit($limit)->offset($offset)->orderBy(['id' => SORT_DESC])->all()
        ];
    }
}