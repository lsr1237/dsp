<?php

namespace backend\controllers;

use backend\models\AuthItem;
use Yii;
use yii\helpers\Json;
use backend\services\AuthService;
use backend\bases\BackendController;

class RoleController extends BackendController
{
    /**
     * 获取所有角色
     */
    public function actionIndex()
    {
        $results = Yii::$app->authManager->getRoles();
        $data = [];
        if ($results) {
            foreach ($results as $row) {
                $data[] = ['name' => $row->name, 'description' => $row->description];
            }
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => $data
        ]);
    }

    public function actionAdd() {
        $request = Yii::$app->request;
        $name = trim($request->post('name',''));
        $description = trim($request->post('description',''));
        if (empty($name)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入角色名称']);
        }
        if (empty($description)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入描述']);
        }
        $auth = Yii::$app->authManager;
        if ($auth->getRole($name)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '角色已存在']);
        }
        $role = $auth->createRole($name);
        $role->description = $description;
        if (!$auth->add($role)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '角色添加失败']);
        } else {
            AuthService::assignDefaultAuth($name);
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
    }

    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $oldName = trim($request->get('name',''));
        $name = trim($request->post('name',''));
        $description = $request->post('description','');
        if (empty($name)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入角色名称']);
        }
        if (empty($description)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入描述']);
        }
        $auth = Yii::$app->authManager;
        $role = $auth->createRole($name);
        $role->description = $description;
        if (!$auth->update($oldName, $role)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '角色修改失败']);
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
    }

    public function actionDetail()
    {
        $request = Yii::$app->request;
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($request->get('name'));
        if (!$role) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => $role
        ]);
    }

    // 删除角色
    public function actionDel()
    {
        $request = Yii::$app->request;
        $name = $request->post('item_name','');
        $authItem = AuthItem::find()->where(['name' => trim($name)])->one();
        if ($authItem->delete()) {
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '删除失败']);
    }
}