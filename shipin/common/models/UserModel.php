<?php

namespace common\models;

use Yii;
use common\bases\CommonModel;

class UserModel extends CommonModel
{
    const STATE_ACTIVE = 1; // 正常
    const STATE_INACTIVE = 2; // 黑名单

    const UPLOAD_LIMIT = 30; // 上传次数限制

    /**
     * 添加用户
     * @param array $data
     * @return bool|User
     */
    public static function add($data)
    {
        $model = new User();
        $model->setAttributes($data);
        if ($model->validate() && $model->save()) {
            return $model;
        } else {
            Yii::error($model->getErrors());
            return false;
        }
    }

    /**
     * 更新用户
     * @param $cond
     * @param $data
     * @return bool|null|static|User
     */
    public static function updateByCond($cond, $data)
    {
        $model = User::findOne($cond);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate() && $model->save()) {
                return $model;
            }
        }
        return false;
    }

    /**
     * 手机号查找用户
     * @param string $mobile
     * @return null|static|User
     */
    public static function getUserByMobile($mobile)
    {
        return User::findOne(['mobile' => $mobile]);
    }

    /**
     * 校验密码
     * @param $password
     * @param $user
     * @return bool
     */
    public static function validatePassword($password, $user)
    {
        return Yii::$app->security->validatePassword($password, $user->password);
    }

    /**
     * 修改密码
     * @param $mobile
     * @param $newPassword
     * @return bool|User|UserModel|null
     * @throws \yii\base\Exception
     */
    public static function updatePasswordByMobile($mobile, $newPassword)
    {
        $user = self::getUserByMobile($mobile);
        if ($user) {
            $user->password = Yii::$app->security->generatePasswordHash($newPassword);
            return $user->save() ? $user : false;
        } else {
            return false;
        }
    }

    /**
     * 用户管理-获取用户数据（包含黑白名单）
     * @param integer $offset 查询的基准数
     * @param integer $limit 查询的记录数
     * @param string $mobile 手机号码
     * @param string $name 姓名
     * @param integer $beginAt 注册开始时间
     * @param integer $endAt 注册截止时间
     * @param integer $state 用户状态
     * @param string number 会员编号
     * @param string|integer num 可用次数
     * @param integer|string $wxId
     * @return array 返回查询的结果集/记录条数
     */
    public static function getUserList($offset, $limit, $mobile, $name, $beginAt, $endAt, $state, $number, $num = '', $wxId = '')
    {
        $data = [];
        $userModel = User::find()
            ->with('memberCard');
        if ($mobile) {
            $userModel->andWhere(['user.mobile' => trim($mobile)]);
        }
        if ($name) {
            $userModel->andWhere(['user.name' => trim($name)]);
        }
        if ($number) {
            $userModel->andWhere(['user.number' => trim($number)]);
        }
        if ($beginAt != '') {
            $beginAt = date('Y-m-d 00:00:00', (int)$beginAt); // 时间戳转字符串
            $userModel->andWhere(['>=', 'user.created_at', $beginAt]); // 注册起始时间
        }
        if ($endAt != '') {
            $endAt = date('Y-m-d 23:59:59', (int)$endAt); // 时间戳转字符串
            $userModel->andWhere(['<=', 'user.created_at', $endAt]); // 注册截止时间
        }
        if ($state) {
            $userModel->andWhere(['user.state' => intval($state)]);
        } else {
            $userModel->andWhere(['<>', 'user.state', UserModel::STATE_INACTIVE]);
        }
        if ($wxId !== '') {
            $userModel->andWhere(['wx_id' => $wxId]);
        }
        if ($num !== '') {
            $userModel->andWhere(['num' => $num]);
        }
        $data['count'] = (int)$userModel->count('user.id');
        $data['result'] = $userModel->offset($offset)->limit($limit)->orderBy(['user.id' => SORT_DESC])->all();
        return $data;
    }

    /**
     * 用户管理-禁用-按ID查询用户信息
     * @param integer $userId 用户ID
     * @return object|User 返回查询的结果
     */
    public static function findUserById($userId)
    {
        return User::findOne(['id' => $userId]);
    }

    /**
     * 用户管理-移入/出黑名单-根据ID 更新用户信息
     * @param integer $id 支付ID
     * @param array $data 参数 字段名称 => 值
     * @return bool|User 成功返回ID,失败返回false
     */
    public static function updateById($id, $data)
    {
        $model = User::findOne(['id' => $id]);
        if ($model) {
            $model->setAttributes($data);
            if ($model->validate()) {
                if ($model->save()) {
                    return $model;
                }
            }
            Yii::error($model->getErrors());
        }
        return false;
    }

    /**
     *
     * @param $cond
     * @return array|null|\yii\db\ActiveRecord|User
     */
    public static function findOneByCond($cond)
    {
        return User::find()->where($cond)->one();
    }
}