<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=sp',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'redis' => [
            'class' => 'common\extend\redis\RedisClient',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 2,
            'enable_stats' => true,
        ],
        'mutex' => [
            'class' => 'yii\redis\Mutex',
            'redis' => [
                'hostname' => 'localhost',
                'port' => 6379,
                'database' => 2,
                'password' => null,
            ]
        ],
    ],
];
