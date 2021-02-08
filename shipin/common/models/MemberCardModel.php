<?php

namespace common\models;


use common\bases\CommonModel;

class MemberCardModel extends CommonModel
{
    const STATE_ACTIVE = 1;
    const STATE_INACTIVE = 2;

    const TYPE_VIP = 1; // vip会员
    const TYPE_NUM = 2; // 次数会员

    /**
     * 添加
     * @param array $data
     * @return bool
     */
    public static function add($data)
    {
        $model = new MemberCard();
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
        $model = MemberCard::findOne($cond);
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
     * @return null|static|MemberCard
     */
    public static function findOneByCond($cond)
    {
        return MemberCard::findOne($cond);
    }

    /**
     * 获取有效会员卡信息
     * @param string $type 条件
     * @param int $wxId
     * @return array|\yii\db\ActiveRecord[]|MemberCard[]
     */
    public static function getActiveCardsByTypeAndWxId($type, $wxId)
    {
        return MemberCard::find()
            ->where(['state' => self::STATE_ACTIVE, 'type' => $type, 'wx_id' => $wxId])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->all();
    }

    /** 查找所有的会员卡
     * @param array $cond
     * @return MemberCard[]
     */
    public static function getAllByCond($cond)
    {
        $model = MemberCard::find();
        if ($cond) {
            $model->where($cond);
        }
        return $model->all();
    }
}