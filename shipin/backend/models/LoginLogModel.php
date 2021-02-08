<?php

namespace backend\models;

use common\bases\CommonModel;

class LoginLogModel extends CommonModel
{
    /**
     * 添加
     * @param array $data 参数
     * @return bool
     */
    public static function add($data)
    {
        $model = new LoginLog();
        $model->setAttributes($data);
        if ($model->validate()) {
            if ($model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取列表信息
     * @param string $username 用户名
     * @param integer $offset 查询的偏移量
     * @param integer $limit 查询的记录数
     * @param array $cond 补充条件
     * @return mixed
     */
    public static function getList($username, $offset, $limit, $cond = [])
    {
        $model = LoginLog::find()->joinWith('admin');
        if (!empty($username)) {
            $model->where(['admin.username' => $username]);
        }
        if (count($cond) > 0) {
            $model->andWhere($cond);
        }
        $result['count'] = $model->count('login_log.id');
        $result['list'] = $model->offset($offset)->limit($limit)->orderBy(['login_log.id' => SORT_DESC])->all();
        return $result;
    }
}