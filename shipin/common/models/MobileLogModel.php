<?php

namespace common\models;

use common\bases\CommonModel;

class MobileLogModel extends CommonModel
{
    const TYPE_AUTHENTICATION_CODE = 'auth_code'; // 短信验证码

    const STATE_CHARGE = '1'; // 不收费
    const STATE_FREE_OF_CHARGE = '0'; // 收费

    /**
     * 获取短信记录
     * @param integer $offset 查询的基准数
     * @param integer $limit 查询的记录数
     * @param string $mobile 手机号
     * @param string $type 短信类型
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getMobileLogList($offset, $limit, $mobile, $type)
    {
        $model = MobileLog::find()
            ->with('user');
        if ($mobile != '') {
            $model->andWhere(['mobile' => $mobile]);
        }
        if ($type != '') {
            $model->andWhere(['type' => $type]);
        }
        return [
            'count' => $model->count(),
            'result' => $model->orderBy(['id' => SORT_DESC])->offset($offset)->limit($limit)->all()
        ];
    }

    /**
     * 添加记录
     * @param array $data
     * @return bool 成功返回true、失败false
     */
    public static function add($data)
    {
        $model = new MobileLog();
        $model->setAttributes($data);
        if ($model->validate()) {
            $res = $model->save();
            if ($res) {
                return true;
            }
        }
        return false;
    }
}