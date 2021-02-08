<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php',
    require __DIR__ . '/../../common/config/video.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
          ],
    ],
    'components' => [
        'log' => [
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
                    'logFile' => '@console/runtime/logs/wx.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['query'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@console/runtime/logs/query.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['video'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@console/runtime/logs/video.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
            ],
        ],
    ],
    'params' => $params,
];
