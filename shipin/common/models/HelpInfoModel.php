<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/11/23
 * Time: 9:33
 */

namespace common\models;


use common\bases\CommonModel;

class HelpInfoModel extends CommonModel
{
    const STATE_SHOW = '1'; // 显示
    const STATE_HIDE = '2'; // 隐藏

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
     * 添加问答
     * @param $data
     * @return bool|string
     */
    public static function add($data)
    {
        $model = new HelpInfo();
        $model->setAttributes($data);
        $result = $model->save();
        if ($result) {
            return $model->id;
        }
        return false;
    }

    /**
     * 编辑问答
     * @param $id
     * @param $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $model = HelpInfo::findOne(['id' => $id]);
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
     * 获得问答列表
     * @param $offset
     * @param $limit
     * @param $state
     * @param $wxId
     * @return array
     */
    public static function getList($offset, $limit, $state, $wxId)
    {
        $model = HelpInfo::find();
        if ($state != '') {
            $model->andWhere(['state' => $state]);
        }
        if ($wxId !== '') {
            $model->andWhere(['wx_id' => $wxId]);
        }
        return [
            'count' => (int)$model->count(),
            'result' => $model->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])->offset($offset)->limit($limit)->all()
        ];
    }

    /**
     * 获取一条问答信息
     * @param $cond
     * @return HelpInfo|null
     */
    public static function getOne($cond)
    {
        return HelpInfo::findOne($cond);
    }

    /**根据条件查找所有记录
     * @param $cond
     * @return array|\yii\db\ActiveRecord[]|HelpInfo[]
     */
    public static function getAllByCond($cond)
    {
        return HelpInfo::find()->where($cond)->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])->all();
    }

    /**
     * 删除记录
     * @param $id
     * @return int
     */
    public static function delete($id)
    {
        return HelpInfo::deleteAll(['id' => $id]);
    }
}