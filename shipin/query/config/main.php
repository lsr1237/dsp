<?php
use yii\web\GroupUrlRule;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'query\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\QueryUser',
            'enableAutoLogin' => false,
            'enableSession' =>false,
            'loginUrl' => null
        ],
//        'session' => [
//            // this is the name of the session cookie used for login on the frontend
//            'name' => 'dspjj-frontend',
//        ],
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
                    'categories' => ['wx'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@query/runtime/logs/wx.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['wxTicket'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@query/runtime/logs/wxTicket.log',
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
            'rules' => [
                new GroupUrlRule([
                    'prefix' => '1',
                    'routePrefix' => '',
                    'rules' => [
                        #### 微信小程序相关 #####
                        # 微信登陆
                        [
                            'pattern' => 'wx-login',
                            'route' => 'wx/login',
                            'verb' => 'POST'
                        ],
                        # 名称查询
                        [
                            'pattern' => 'wx-query',
                            'route' => 'site/query',
                            'verb' => 'GET'
                        ],
                        # 微信ticket回调通知
                        [
                            'pattern' => 'wx-ticket-callback',
                            'route' => 'wx/callback',
                            'verb' => 'POST'
                        ],
                        # 微信ticket回调通知
                        [
                            'pattern' => 'wx-ticket-callback',
                            'route' => 'wx/callback',
                            'verb' => 'GET'
                        ],
                        # 联系我们
                        [
                            'pattern' => 'contact',
                            'route' => 'site/contact',
                            'verb' => 'GET'
                        ],
                        # 获取公共阐述
                        [
                            'pattern' => 'common',
                            'route' => 'site/common',
                            'verb' => 'GET'
                        ],
                        # 我的信息
                        [
                            'pattern' => 'my',
                            'route' => 'site/my',
                            'verb' => 'GET'
                        ],
                        # 添加监控名称
                        [
                            'pattern' => 'monitor',
                            'route' => 'site/monitor',
                            'verb' => 'POST'
                        ],
                        # 更新监控状态
                        [
                            'pattern' => 'update-monitor',
                            'route' => 'site/update-monitor',
                            'verb' => 'POST'
                        ],
                        # 获取监控列表
                        [
                            'pattern' => 'monitor-list',
                            'route' => 'site/monitor-list',
                            'verb' => 'GET'
                        ],
                    ],
                ]),
            ]
        ],
    ],
    'params' => $params,
];
