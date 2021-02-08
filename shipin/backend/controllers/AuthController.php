<?php

namespace backend\controllers;

use backend\models\AuthItemChildModel;
use backend\models\MenuModel;
use Yii;
use yii\helpers\Json;
use backend\models\AuthItemModel;
use backend\bases\BackendController;

class AuthController extends BackendController
{
    public function actionIndex()
    {
        $results = $data = [];
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $name = $request->get('name', '');
        $result = AuthItemModel::getList($offset, $limit, $name, AuthItemModel::TYPE_PERMISSION);
        foreach ($result['list'] as $row) {
            $data[] = [
                'name' => $row->name,
                'description' => $row->description,
                'p_name' => $row->p_name,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'count' => (int)$result['count'],
            'results' => $data
        ]);
    }

    // 添加权限
    public function actionAdd()
    {
        $request = Yii::$app->request;
        $name = trim($request->post('name', ''));
        $description = trim($request->post('description', ''));
        $pName = trim($request->post('p_name', ''));
        if (empty($name)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入权限名称']);
        }
        if (empty($description)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入描述']);
        }
        //判断权限是否已经存在
        $checkExist = AuthItemModel::findOneByCond(['name' => $name]);
        if ($checkExist) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '权限名称已存在']);
        }
        $data = [
            'name' => $name,
            'description' => $description,
            'type' => authItemModel::TYPE_PERMISSION,
            'p_name' => $pName,
            'created_at' => time(),
            'updated_at' => time()
        ];
        $result = AuthItemModel::add($data);
        if (!$result) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '权限修改失败']);
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
    }

    // 编辑权限
    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $oldName = trim($request->post('old_name', ''));
        $name = trim($request->post('name', ''));
        $description = $request->post('description', '');
        $pName = trim($request->post('p_name', ''));
        if (empty($name)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入权限名称']);
        }
        if (empty($description)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入描述']);
        }
        //判断权限是否已经存在
        $checkExist = AuthItemModel::findOneByCond(['name' => $name, 'description' => $description, 'p_name' => $pName]);
        if ($checkExist) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '权限名称已存在']);
        }
        $data = [
            'name' => $name,
            'p_name' => $pName,
            'description' => $description,
            'updated_at' => time(),
        ];
        $result = AuthItemModel::update($oldName, $data);
        if (!$result) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '权限修改失败']);
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
    }

    public function actionDelete()
    {
        $request = Yii::$app->request;
        $name = $request->post('itemname', '');
        $result = AuthItemModel::del($name);
        if ($result) {
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '删除失败']);
    }

    public function actionDetail()
    {
        $request = Yii::$app->request;
        $name = trim($request->get('name', ''));
        $auth = AuthItemModel::getOneAsArrayByName($name);
        if (!$auth) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => $auth
        ]);
    }

    public function actionRoleAuths()
    {
        $request = Yii::$app->request;
        $name = $request->get('name', '');
        $result = $data = $roleAuth = [];
        $result = AuthItemChildModel::findAllByCond(['parent' => trim($name)]);  // 获取所有改角色已经拥有的权限以及菜单
        foreach ($result as $row) {
            $roleAuth[] = $row->child;
        }
        $result = AuthItemModel::findAllByCond(['type' => authItemModel::TYPE_PERMISSION]); // 获取所有的角色和菜单
        $routes = [];
        foreach ($result as $row) {
            $item = [
                'id' => $row->name,
                'label' => $row->description,
            ];
            if (preg_match("/^backend_menus(.)*$/", $row->name)) { // 判断菜单
                continue;
            }
            $routes[!empty($row->p_name) ? $row->p_name : 'common_api'][] = $item;
        }
        $menus = MenuModel::getAllMenus(); // 获取所有的导航菜单
        $menus['通用接口'] = [
            'children' => [
                'common_api' => [
                    'title' => '通用接口',
                ],
            ]
        ];
        foreach ($menus as $key => $row) {
            $data[$key] = [
                'label' => $key,
                'disabled' => true,
                'children' => []
            ];
            if (isset($row['children']) && !empty($row['children'])) {
                foreach ($row['children'] as $menuName => $menuDetail) {
                    $data[$key]['children'][] = [
                        'label' => $menuDetail['title'] ?? '',
                        'id' => $menuName,
                        'disabled' => ($key == '通用接口'),
                        'children' => isset($routes[$menuName]) ? $routes[$menuName] : []
                    ];
                }
            }
        }
        $data = array_values($data) ?? [];
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'results' => $data,
            'default' => $roleAuth
        ]);
    }

    public function actionAssign()
    {
        $request = Yii::$app->request;
        $parent = $request->post('parent', '');
        $child = $request->post('child', '');
        $enable = (int)$request->post('enable', '');
        if ($enable == 2) {
            $model = AuthItemChildModel::findOneByCond(['parent' => trim($parent), 'child' => trim($child)]);
            if ($model && $model->delete()) {
                return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '取消成功']);
            }
        } elseif ($enable == 1) {
            $model = AuthItemChildModel::findOneByCond(['parent' => trim($parent), 'child' => trim($child)]);
            if ($model) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '该角色已有该权限']);
            } else {
                $childModel = AuthItemModel::findOneByCond(['name' => trim($child)]);
                if (!$childModel) {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '该权限尚未添加']);
                }
                $data = [
                    'parent' => $parent,
                    'child' => $child,
                ];
                $result = AuthItemChildModel::add($data);
                if ($result) {
                    return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '添加成功']);
                }
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '设置失败']);
    }
}
