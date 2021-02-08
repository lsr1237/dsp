<?php

namespace backend\models;

use Yii;
use common\bases\CommonModel;

class MenuModel extends CommonModel
{
    const IS_PARENT = 0; // 父类菜单

    public static function getAllMenus()
    {
        return [
            '用户管理' => [
                'icon' => 'fa-address-book-o',
                'children' => [
                    'backend_menus_members' => [
                        'title' => '用户管理',
                        'route' => '/vue-dist/#/users',
                        'children' => []
                    ],
                    'backend_menus_black_list' => [
                        'title' => '黑名单用户',
                        'route' => '/vue-dist/#/black-users',
                        'children' => []
                    ],
                    'backend_menus_analysis_log' => [
                        'title' => '解析记录',
                        'route' => '/vue-dist/#/analysis-log',
                        'children' => []
                    ],
                    'backend_menus_err_log' => [
                        'title' => '错误记录',
                        'route' => '/vue-dist/#/err-log',
                        'children' => []
                    ],
                ]
            ],
            '产品管理' => [
                'icon' => 'fa-delicious',
                'children' => [
                    'backend_menus_product_use' => [
                        'title' => '使用记录',
                        'route' => '/vue-dist/#/use-log',
                        'children' => []
                    ],
                ]
            ],
            '反馈管理' => [
                'icon' => 'fa-envelope-open',
                'children' => [
                    'backend_menus_feedback' => [
                        'title' => '反馈记录',
                        'route' => '/vue-dist/#/feedback',
                        'children' => []
                    ],
                ]
            ],
            '会员管理' => [
                'icon' => 'fa-stack-overflow',
                'children' => [
                    'backend_menus_recharge_log' => [
                        'title' => '会员充值记录',
                        'route' => '/vue-dist/#/recharge-log',
                        'children' => []
                    ],
                ]
            ],
            '帮助中心' => [
                'icon' => 'fa-handshake-o',
                'children' => [
                    'backend_menus_help_info' => [
                        'title' => '问题配置',
                        'route' => '/vue-dist/#/help-info',
                        'children' => []
                    ],
                ]
            ],
            '系统管理' => [
                'icon' => 'fa  fa-gear',
                'children' => [
                    'backend_menus_permissions_admins' => [
                        'title' => '管理员列表',
                        'route' => '/vue-dist/#/admins',
                        'children' => []
                    ],
                    'backend_menus_permissions_roles' => [
                        'title' => '角色管理',
                        'route' => '/vue-dist/#/roles',
                        'children' => []
                    ],
                    'backend_menus_permissions_auths' => [
                        'title' => '权限管理',
                        'route' => '/vue-dist/#/auths',
                        'children' => []
                    ],
                    'backend_menus_login_log' => [
                        'title' => '登入日志',
                        'route' => '/vue-dist/#/login-log',
                        'children' => []
                    ],
                    'backend_menus_app' => [
                        'title' => 'app版本信息',
                        'route' => '/vue-dist/#/app',
                        'children' => []
                    ],
                    'backend_menus_app_basic' => [
                        'title' => 'app基本信息配置',
                        'route' => '/vue-dist/#/app-basic',
                        'children' => []
                    ],
                    'backend_menus_content' => [
                        'title' => '公告、banner',
                        'route' => '/vue-dist/#/notice',
                        'children' => []
                    ],
                    'backend_menus_member_card' => [
                        'title' => '会员卡设置',
                        'route' => '/vue-dist/#/member-card',
                        'children' => []
                    ],
                    'backend_menus_query' => [
                        'title' => '名称检测配置',
                        'route' => '/vue-dist/#/query',
                        'children' => []
                    ],
                    'backend_menus_video_conf' => [
                        'title' => '视频文件配置',
                        'route' => '/vue-dist/#/video-conf',
                        'children' => []
                    ],
                ]
            ],
        ];
    }

    private static function getByPermission($menus, $routes)
    {
        $result = [];
        foreach ($menus as $key => $menu) {
            // 第二层或以下没有子菜单的话就是叶节点，检查菜单权限
            if (empty($menu['children'])) {
                if (in_array($key, $routes)) {
                    $result[] = [
                        'title' => $menu['title'],
                        'route' => $menu['route']
                    ];
                }
                // 有子菜单检查所有子菜单的权限
            } else {
                $children = self::getByPermission($menu['children'], $routes);
                if (!empty($children)) {
                    $result[] = [
                        'title' => $key,
                        'icon' => $menu['icon'],
                        'children' => $children
                    ];
                }
            }
        }
        return $result;
    }

    public static function getMenus()
    {
        $routes = Yii::$app->user->identity->getRoutes();
        return self::getByPermission(self::getAllMenus(), $routes);
    }
}