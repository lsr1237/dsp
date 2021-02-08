<?php
namespace backend\controllers;

use Yii;
use yii\helpers\Json;
use backend\models\MenuModel;
use backend\bases\BackendController;

class MenuController extends BackendController
{

    /**
     * 获取用户菜单列表
     */
    public function actionMine()
    {
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => MenuModel::getMenus()
        ]);
    }

    /**
     * 获取父级菜单
     * @return string
     */
    public function actionIndex()
    {
        $menus = MenuModel::getAllMenus();
        $data = [];
        foreach ($menus as $key => $menu) {
            $data[$key]['name'] = $key;
            if (isset($menu['children']) && !empty($menu['children'])) {
                foreach ($menu['children'] as $menuName => $menuDetail) {
                    $data[$key]['children'][] = [
                        'name' => $menuDetail['title'] ?? '',
                        'value' => $menuName,
                    ];
                }
            }
        }
        $data = array_values($data);
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => $data
        ]);
    }
}