<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/7
 * Time: 16:12
 */

namespace common\models;


use common\bases\CommonModel;

class NoticeModel extends CommonModel
{
    const STATE_SHOW = '1'; // 显示
    const STATE_HIDE = '2'; // 隐藏

    const TYPE_NOTICE = 1; // 公告
    const TYPE_BANNER = 2; // BANNER

    const JUMP = 1; // 跳转
    const NO_JUMP = 2; // 不跳转

    /**
     * 添加公告
     * @param $data
     * @return bool|string
     */
    public static function add($data)
    {
        $model = new Notice();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return $model->id;
        }
        return false;
    }

    /**
     * 编辑公告
     * @param $id
     * @param $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $model = Notice::findOne(['id' => $id]);
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
     * 获得公告列表
     * @param $offset
     * @param $limit
     * @param $state
     * @param $type
     * @param $wxId
     * @return array
     */
    public static function getList($offset, $limit, $state, $type, $wxId)
    {
        $model = Notice::find();
        if ($state != '') {
            $model->andWhere(['state' => $state]);
        }
        if ($type != '') {
            $model->andWhere(['type' => $type]);
        }
        if ($wxId != '') {
            $model->andWhere(['wx_id' => $wxId]);
        }
        return [
            'count' => (int)$model->count(),
            'result' => $model->orderBy(['sort' => SORT_DESC, 'id' => SORT_DESC])->offset($offset)->limit($limit)->all()
        ];
    }

    /**
     * 删除公告
     * @param $id
     * @return int
     */
    public static function delNoticeById($id)
    {
        return Notice::deleteAll(['id' => $id]);
    }

    /**
     * 获取一条数据
     * @param $id
     * @return Notice|null
     */
    public static function getOneById($id)
    {
        return Notice::findOne(['id' => $id]);
    }

    /**
     * 获取最新的一条公告
     * @return array|null|\yii\db\ActiveRecord|Notice
     */
    public static function getLastOneNotice()
    {
        return Notice::find()
            ->where(['type' => self::TYPE_NOTICE, 'state' => self::STATE_SHOW])
            ->orderBy(['sort' => SORT_DESC, 'id' => SORT_DESC])
            ->one();
    }

    /**
     * @param $cond
     * @return array|\yii\db\ActiveRecord[]|Notice[]
     */
    public static function getNoticeByCond($cond)
    {
        return Notice::find()->where($cond)->orderBy(['sort' => SORT_DESC, 'id' => SORT_DESC])->all();
    }
}