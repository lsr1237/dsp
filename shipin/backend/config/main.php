<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php',
    require __DIR__ . '/../../common/config/video.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'backend\models\Admin',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'dspjj-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['ali'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@backend/runtime/logs/ali.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['wx'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@backend/runtime/logs/wx.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                ##### 权限管理相关API #######################
                # 管理员列表
                [
                    'pattern' => 'admins',
                    'route' => 'admin/index',
                    'verb' => 'GET'
                ],
                # 管理员详情
                [
                    'pattern' => 'admin-detail/<id:\d+>',
                    'route' => 'admin/detail',
                    'verb' => 'GET'
                ],
                # 添加管理员
                [
                    'pattern' => 'add-admin',
                    'route' => 'admin/add',
                    'verb' => 'POST'
                ],
                # 编辑管理员
                [
                    'pattern' => 'update-admin/<id:\d+>',
                    'route' => 'admin/update',
                    'verb' => 'PUT'
                ],
                # 设置离职
                [
                    'pattern' => 'set-admin-leave',
                    'route' => 'admin/set-leave',
                    'verb' => 'PUT'
                ],
                # 设置新密码
                [
                    'pattern' => 'set-admin-password',
                    'route' => 'admin/set-password',
                    'verb' => 'PUT'
                ],
                # 设置解锁
                [
                    'pattern' => 'set-admin-unlock',
                    'route' => 'admin/set-unlock',
                    'verb' => 'PUT'
                ],
                # 角色列表
                [
                    'pattern' => 'roles',
                    'route' => 'role/index',
                    'verb' => 'GET'
                ],
                # 添加角色
                [
                    'pattern' => 'add-role',
                    'route' => 'role/add',
                    'verb' => 'POST'
                ],
                # 编辑角色
                [
                    'pattern' => 'update-role/<name:\w+>',
                    'route' => 'role/update',
                    'verb' => 'PUT'
                ],
                # 编辑角色
                [
                    'pattern' => 'del-role',
                    'route' => 'role/del',
                    'verb' => 'PUT'
                ],
                # 角色详情
                [
                    'pattern' => 'role-detail/<name:\w+>',
                    'route' => 'role/detail',
                    'verb' => 'GET'
                ],
                # 菜单列表
                [
                    'pattern' => 'menus',
                    'route' => 'menu/index',
                    'verb' => 'GET'
                ],
                # 权限列表
                [
                    'pattern' => 'auths',
                    'route' => 'auth/index',
                    'verb' => 'GET'
                ],
                # 添加权限
                [
                    'pattern' => 'add-auth',
                    'route' => 'auth/add',
                    'verb' => 'POST'
                ],
                # 编辑权限
                [
                    'pattern' => 'update-auth',
                    'route' => 'auth/update',
                    'verb' => 'PUT'
                ],
                # 权限详情
                [
                    'pattern' => 'auth-detail/<name:\w+>',
                    'route' => 'auth/detail',
                    'verb' => 'GET'
                ],
                # 删除权限
                [
                    'pattern' => 'delete-auth',
                    'route' => 'auth/delete',
                    'verb' => 'POST'
                ],
                # 分配权限
                [
                    'pattern' => 'assign-auth',
                    'route' => 'auth/assign',
                    'verb' => 'POST'
                ],
                # 角色权限
                [
                    'pattern' => 'role-auths',
                    'route' => 'auth/role-auths',
                    'verb' => 'GET'
                ],
                # 登入日志
                [
                    'pattern' => 'login-log',
                    'route' => 'admin/login-log',
                    'verb' => 'GET'
                ],
                ########### 获取App详细信息 #############
                # 获取app信息以及所有版本号
                [
                    'pattern' => 'app-info/<platform:\w+>',
                    'route' => 'app/app-info',
                    'verb' => 'GET'
                ],
                # 获取版本更新历史记录
                [
                    'pattern' => 'version-msg',
                    'route' => 'app/get-version',
                    'verb' => 'GET'
                ],
                # 编辑app信息
                [
                    'pattern' => 'edit-app',
                    'route' => 'app/edit',
                    'verb' => 'POST'
                ],
                # 获取当前app信息
                [
                    'pattern' => 'app',
                    'route' => 'app/index',
                    'verb' => 'GET'
                ],
                # 获取App基础信息
                [
                    'pattern' => 'app-basic',
                    'route' => 'app/basic',
                    'verb' => 'GET'
                ],
                # 更新App基本信息
                [
                    'pattern' => 'update-app-basic',
                    'route' => 'app/update-basic',
                    'verb' => 'POST'
                ],
                # 上传apk文件
                [
                    'pattern' => 'upload-apk',
                    'route' => 'site/upload-apk',
                    'verb' => 'POST'
                ],
                ##### 用户管理相关API #######################
                # 用户列表/用户黑名单
                [
                    'pattern' => 'users',
                    'route' => 'user/index',
                    'verb' => 'GET'
                ],
                # 用户移入-移出黑名单
                [
                    'pattern' => 'move-black',
                    'route' => 'user/move-to-black',
                    'verb' => 'POST'
                ],
                # 用户修改
                [
                    'pattern' => 'user-update',
                    'route' => 'user/update',
                    'verb' => 'POST'
                ],
                # 反馈列表
                [
                    'pattern' => 'feedback',
                    'route' => 'user/feedback',
                    'verb' => 'GET'
                ],
                # 解析记录
                [
                    'pattern' => 'analysis-log',
                    'route' => 'user/analysis-log',
                    'verb' => 'GET'
                ],
                # 错误记录
                [
                    'pattern' => 'err-log',
                    'route' => 'user/err-log',
                    'verb' => 'GET'
                ],
                # 域名统计
                [
                    'pattern' => 'domain',
                    'route' => 'user/domain',
                    'verb' => 'GET'
                ],
                # 清空域名统计
                [
                    'pattern' => 'empty-domain',
                    'route' => 'user/empty-domain',
                    'verb' => 'POST'
                ],
                # 获取小程序名称数组
                [
                    'pattern' => 'applet-arr',
                    'route' => 'site/applet',
                    'verb' => 'GET'
                ],
                ##### 公告banner相关API ######
                # 公告列表
                [
                    'pattern' => 'notice',
                    'route' => 'content/notice',
                    'verb' => 'GET'
                ],
                # 添加公告
                [
                    'pattern' => 'notice-add',
                    'route' => 'content/notice-add',
                    'verb' => 'POST'
                ],
                # 编辑公告
                [
                    'pattern' => 'notice-update/<id:\d+>',
                    'route' => 'content/notice-update',
                    'verb' => 'PUT'
                ],
                # 获取公告
                [
                    'pattern' => 'get-notice/<id:\d+>',
                    'route' => 'content/get-notice',
                    'verb' => 'GET'
                ],
                # 删除公告
                [
                    'pattern' => 'del-notice',
                    'route' => 'content/notice-del',
                    'verb' => 'POST'
                ],
                # 公告图片上传
                [
                    'pattern' => 'upload',
                    'route' => 'site/upload',
                    'verb' => 'POST'
                ],
                #### 系统设置相关API #####
                # 会员卡列表
                [
                    'pattern' => 'member-card',
                    'route' => 'sys/member-card',
                    'verb' => 'GET'
                ],
                # 添加会员卡
                [
                    'pattern' => 'member-card-add',
                    'route' => 'sys/member-card-save',
                    'verb' => 'POST'
                ],
                # 更新会员卡
                [
                    'pattern' => 'member-card-update',
                    'route' => 'sys/member-card-save',
                    'verb' => 'PUT'
                ],
                # 获取视频文件配置
                [
                    'pattern' => 'video-conf',
                    'route' => 'sys/video-conf',
                    'verb' => 'GET'
                ],
                # 修改视频文件配置
                [
                    'pattern' => 'update-video-conf',
                    'route' => 'sys/video-conf-update',
                    'verb' => 'POST'
                ],
                #### 产品相关API #####
                # 产品使用列表
                [
                    'pattern' => 'use-list',
                    'route' => 'use-log/list',
                    'verb' => 'GET'
                ],
                #### 会员相关API #####
                # 会员充值列表
                [
                    'pattern' => 'recharge-log',
                    'route' => 'user/recharge-log',
                    'verb' => 'GET'
                ],
                # 支付记录查询
                [
                    'pattern' => 'pay-query',
                    'route' => 'pay/query',
                    'verb' => 'POST'
                ],
                # 退款
                [
                    'pattern' => 'refund',
                    'route' => 'pay/refund',
                    'verb' => 'POST'
                ],
                #### 名称检测 #####
                # 名称检测配置
                [
                    'pattern' => 'query-conf',
                    'route' => 'query-conf/index',
                    'verb' => 'GET'
                ],
                [
                    'pattern' => 'query-conf-update',
                    'route' => 'query-conf/update',
                    'verb' => 'POST'
                ],
                #### 帮助中心API #####
                # 帮助中心列表
                [
                    'pattern' => 'help-list',
                    'route' => 'help-info/list',
                    'verb' => 'GET'
                ],
                # 更新帮助中心
                [
                    'pattern' => 'help-update',
                    'route' => 'help-info/update',
                    'verb' => 'POST'
                ],
                # 删除帮助中心记录
                [
                    'pattern' => 'help-del',
                    'route' => 'help-info/del',
                    'verb' => 'POST'
                ],
            ],
        ],

    ],
    'params' => $params,
];
