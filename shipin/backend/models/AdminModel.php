<?php

namespace backend\models;

use common\bases\CommonModel;
use Yii;

class AdminModel extends CommonModel
{
    const SIGNED = 1; // 在职
    const RESIGNED = 2; // 离职
    const LOCK = 3; // 锁定

    const COLLECTION_TEAM_ONE = 1; // 催收组1
    const COLLECTION_TEAM_TWO = 2; // 催收组2
    const COLLECTION_TEAM_THREE = 3; // 催收组3
    const COLLECTION_TEAM_FOUR = 4; // 催收组4
    const COLLECTION_TEAM_FIVE = 5; // 催收组5
    const COLLECTION_TEAM_SIX = 6; // 催收组6
    const COLLECTION_TEAM_SEVEN = 7; // 催收组7
    const COLLECTION_TEAM_EIGHT = 8; // 催收组8
    const COLLECTION_TEAM_NINE = 9; // 催收组9
    const COLLECTION_TEAM_TEN = 10; // 催收组10
    const COLLECTION_TEAM_ARR = [
        self::COLLECTION_TEAM_ONE,
        self::COLLECTION_TEAM_TWO,
        self::COLLECTION_TEAM_THREE,
        self::COLLECTION_TEAM_FOUR,
        self::COLLECTION_TEAM_FIVE,
        self::COLLECTION_TEAM_SIX,
        self::COLLECTION_TEAM_SEVEN,
        self::COLLECTION_TEAM_EIGHT,
        self::COLLECTION_TEAM_NINE,
        self::COLLECTION_TEAM_TEN,
    ];
    const URGE_STAFF = '催收员';
    const URGE_LEADER = '催收组长';

    public static $stateArr = [
        self::SIGNED => '在职',
        self::RESIGNED => '离职'
    ];

    /**
     * 获取催收员，催收组长
     * @param integer $team
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAdmins($team)
    {
        $model = Admin::find()->with('role')->where(['state' => self::SIGNED]);
        if ($team) {
            $model->andWhere(['team' => $team]);
        }
        return $model->all();
    }

    /**
     * 催收员列表
     * @param $offset
     * @param $limit
     * @param string $userName 登陆名
     * @param string $realName 真实姓名
     * @param integer $team 组
     * @param integer $adminId 管理员id
     * @param array $orderBy 排序
     * @return array 返回数组【记录条数，列表数据对象】
     */
    public static function getUrgeStatics($offset, $limit, $userName, $realName, $team, $adminId, $orderBy = ['admin.id' => SORT_DESC])
    {
        $model = Admin::find()
            ->joinWith('role')
            ->where(['auth_assignment.item_name' => [self::URGE_STAFF, self::URGE_LEADER]]);
        if ($team) {
            $model->andWhere(['admin.team' => $team]);
        }
        if ($adminId) {
            $model->andWhere(['admin.id' => $adminId]);
        }
        if ($userName != '') {
            $model->andWhere(['admin.username' => $userName]);
        }
        if ($realName != '') {
            $model->andWhere(['admin.real_name' => $realName]);
        }
        return [
            'count' => $model->count(),
            'result' => $model->offset($offset)->limit($limit)->orderBy($orderBy)->all() // 查询的结果
        ];
    }

    /**
     * 修改用户登陆信息
     * @param integer $adminId 用户id
     * @param string $loginIp 登陆ip
     * @return bool
     */
    public static function updateLoginMsg($adminId, $loginIp)
    {
        $model = Admin::findOne(['id' => $adminId]);
        if ($model) {
            $model->login_ip = $loginIp;
            $model->login_time = date('Y-m-d H:i:s');
            if ($model->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据条件查找管理员
     * @param array|string $cond 条件
     * @return null|static|object
     */
    public static function findAdminByCond($cond)
    {
        return Admin::findOne($cond);
    }

    /**
     * 获取列表
     * @param int $state 状态
     * @param string $realName 真实姓名
     * @param string $username 用户名
     * @param int $offset 查询偏移量
     * @param int $limit 查询记录数
     * @return mixed
     */
    public static function getList($state, $realName, $username, $offset, $limit)
    {
        $model = Admin::find()->with('role');
        if ($state) {
            $model->andWhere(['admin.state' => (int)$state]);
        }
        if ($realName) {
            $model->andWhere(['admin.real_name' => trim($realName)]);
        }
        if ($username) {
            $model->andWhere(['admin.username' => trim($username)]);
        }
        $result['count'] = $model->count('admin.id');
        $result['list'] = $model->offset($offset)->limit($limit)->orderBy(['admin.id' => SORT_DESC])->all();
        return $result;
    }

    /**
     * 增加
     * @param array $data 参数信息
     * @return bool|object
     */
    public static function add($data)
    {
        $model = new Admin();
        $model->setAttributes($data);
        if ($model->validate()) {
            if ($model->save()) {
                return $model;
            }
        }
        return false;
    }

    /**
     * 更新信息
     * @param int $id ID
     * @param array $data 参数信息
     * @return bool|object
     */
    public static function update($id, $data)
    {
        $model = Admin::findOne(['id' => $id]);
        if (!$model) {
            return false;
        }
        $model->setAttributes($data);
        if ($model->validate()) {
            if ($model->save()) {
                return $model;
            }
        }
        return false;
    }

    /**
     * 根据条件查询唯一记录
     * @param string|array $cond 条件
     * @return array|\yii\db\ActiveRecord|null
     */
    public static function findOneByCond($cond)
    {
        return Admin::find()->where($cond)->one();
    }

    /**
     * 根据ID获取管理员信息
     * @param int $id ID
     * @return array|\yii\db\ActiveRecord|null
     */
    public static function findDetailById($id)
    {
        return Admin::find()
            ->select(['admin.username', 'admin.real_name', 'admin.channel', 'auth_assignment.item_name as role', 'admin.team'])
            ->andWhere(['id' => $id])
            ->leftJoin('auth_assignment', 'admin.id = auth_assignment.user_id')
            ->asArray()
            ->one();
    }
}

