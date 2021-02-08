<?php

namespace common\models;

use common\bases\CommonModel;

class AppBasicModel extends CommonModel
{

    const AUDIT_STATE_OPEN = 1;
    /**
     *字段解析
     * config: {
            "wechat":"123456", // 微信号
            "open_vip_txt":"年中大促", // 开通vip文案
            "share_title":"dev", // 首页分享标题
            "button":"", // 公众号按钮名称
            "link":"", // 公众号连接
            "free_num":1, // 每日免费次数
            "reward_num":1, // 邀请奖励次数
            "limit_num":1 // 每日广告限制次数
            "subscription": "" // 微信工作号图片
     *      "audit_state": 2 // 审核状态 1-开启 2-关闭
     }
     */


    /**
     * 获取app基本信息
     * @param int $id 小程序id
     * @return AppBasic|null
     */
    public static function getAppBasic($id)
    {
        return AppBasic::findOne(['wx_id' => $id]);
    }

    /**
     * 新增记录
     * @param array $data 新增的数据
     * @return bool|int
     */
    public static function add($data)
    {
        $model = new AppBasic();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return $model->id;
        }
        return false;
    }

    /**
     * 根据id编辑记录
     * @param integer $id 主键ID
     * @param array $data 更新的数据
     * @return bool
     */
    public static function update($id, $data)
    {
        $model = AppBasic::findOne(['id' => $id]);
        if ($model) {
            $model->setAttributes($data);
            $result = $model->save();
            if ($result) {
                return true;
            }
        }
        return false;
    }
}