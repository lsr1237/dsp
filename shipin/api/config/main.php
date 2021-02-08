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
    'controllerNamespace' => 'api\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
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
                    'categories' => ['ali'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@api/runtime/logs/ali.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['wx'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@api/runtime/logs/wx.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['ffmpeg'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@api/runtime/logs/ffmpeg.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['video'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@api/runtime/logs/video.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['wxTicket'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@api/runtime/logs/wxTicket.log',
                    'maxFileSize' => 51200, // 50M
                    'maxLogFiles' => 10
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['user'],
                    'logVars' => ['_GET', '_POST'],
                    'logFile' => '@api/runtime/logs/user.log',
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
                        #### 公用接口 ####
                        # 注册接口
                        [
                            'pattern' => 'signup',
                            'route' => 'site/signup',
                            'verb' => 'POST'
                        ],
                        # 获取随机值
                        [
                            'pattern' => 'rt',
                            'route' => 'site/rt',
                            'verb' => 'GET'
                        ],
                        # 发送短信验证码
                        [
                            'pattern' => 'send-mobile-code',
                            'route' => 'site/send-mobile-code',
                            'verb' => 'POST'
                        ],
                        # 登陆
                        [
                            'pattern' => 'login',
                            'route' => 'site/login',
                            'verb' => 'POST'
                        ],
                        # 退出
                        [
                            'pattern' => 'logout',
                            'route' => 'site/logout',
                            'verb' => 'POST'
                        ],
                        # 首页
                        [
                            'pattern' => 'home',
                            'route' => 'site/home',
                            'verb' => 'GET'
                        ],
                        # 最新版本
                        [
                            'pattern' => 'latest-version',
                            'route' => 'file/latest-version',
                            'verb' => 'GET'
                        ],
                        # 重置密码
                        [
                            'pattern' => 'forget-pwd',
                            'route' => 'site/forget-pwd',
                            'verb' => 'PUT'
                        ],
                        # 修改密码
                        [
                            'pattern' => 'password',
                            'route' => 'site/password',
                            'verb' => 'PUT'
                        ],
                        # 会员卡列表
                        [
                            'pattern' => 'vip-list',
                            'route' => 'site/vip-list',
                            'verb' => 'GET'
                        ],
                        # 联系客服
                        [
                            'pattern' => 'contact',
                            'route' => 'site/contact',
                            'verb' => 'GET'
                        ],
                        # 帮助中心
                        [
                            'pattern' => 'help',
                            'route' => 'site/help',
                            'verb' => 'GET'
                        ],
                        # 帮助中心带页面类型
                        [
                            'pattern' => 'help-info',
                            'route' => 'site/help-info',
                            'verb' => 'GET'
                        ],
                        # 联系我们
                        [
                            'pattern' => 'contact',
                            'route' => 'site/contact',
                            'verb' => 'GET'
                        ],
                        # 下载视频错误上报
                        [
                            'pattern' => 'save-err',
                            'route' => 'site/save-err',
                            'verb' => 'POST'
                        ],
                        ###### 用户相关API ######
                        # 我的
                        [
                            'pattern' => 'my',
                            'route' => 'user/index',
                            'verb' => 'GET'
                        ],
                        # 观看广告成功任务上报
                        [
                            'pattern' => 'ad-reward',
                            'route' => 'user/ad-reward',
                            'verb' => 'POST'
                        ],
                        # 次数记录
                        [
                            'pattern' => 'num-log',
                            'route' => 'user/num-log',
                            'verb' => 'GET'
                        ],
                        ##### 支付相关API ######
                        # 购买会员确认
                        [
                            'pattern' => 'vip-confirm',
                            'route' => 'pay/confirm',
                            'verb' => 'POST'
                        ],
                        # 购买会员信息提交
                        [
                            'pattern' => 'vip-submit',
                            'route' => 'pay/submit',
                            'verb' => 'POST'
                        ],
                        # 支付宝回调通知
                        [
                            'pattern' => 'ali-callback',
                            'route' => 'pay/ali-callback',
                            'verb' => 'POST'
                        ],
                        # 微信回调通知
                        [
                            'pattern' => 'wx-callback',
                            'route' => 'pay/wx-callback',
                            'verb' => 'POST'
                        ],
                        # 微信退款回调通知
                        [
                            'pattern' => 'wx-refund-callback',
                            'route' => 'pay/wx-refund-callback',
                            'verb' => 'POST'
                        ],
                        # 支付信息查询
                        [
                            'pattern' => 'pay-query',
                            'route' => 'pay/query',
                            'verb' => 'POST'
                        ],
                        # 产品使用情况上报
                        [
                            'pattern' => 'report',
                            'route' => 'site/report',
                            'verb' => 'POST'
                        ],
                        # 反馈
                        [
                            'pattern' => 'feedback',
                            'route' => 'site/feedback',
                            'verb' => 'POST'
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
                        # 微信绑定关联公众号
                        [
                            'pattern' => 'wx-bind-official',
                            'route' => 'site/bind-official',
                            'verb' => 'POST'
                        ],
                        ###### 去水印API ######
                        # 去水印
                        [
                            'pattern' => 'remove-watermark',
                            'route' => 'watermark/remove',
                            'verb' => 'GET'
                        ],
                        # 去水印
                        [
                            'pattern' => 'remove-watermark-n',
                            'route' => 'watermark/remove-n',
                            'verb' => 'GET'
                        ],
                        # 去水印获取连接
                        [
                            'pattern' => 'rm-wm-detail',
                            'route' => 'watermark/detail',
                            'verb' => 'GET'
                        ],
                        ###### 小程序API ######
                        # 视频上传
                        [
                            'pattern' => 'upload-video',
                            'route' => 'video/upload-video',
                            'verb' => 'POST'
                        ],
                        # 图片上传
                        [
                            'pattern' => 'upload-img',
                            'route' => 'video/upload-img',
                            'verb' => 'POST'
                        ],
                        # 视频剪裁尺寸
                        [
                            'pattern' => 'size-cut',
                            'route' => 'video/size-cut',
                            'verb' => 'POST'
                        ],
                        # 剪裁视频长度
                        [
                            'pattern' => 'duration-cut',
                            'route' => 'video/duration-cut',
                            'verb' => 'POST'
                        ],
                        # 获取音频
                        [
                            'pattern' => 'get-audio',
                            'route' => 'video/get-audio',
                            'verb' => 'POST'
                        ],
                        # 视频倒放
                        [
                            'pattern' => 'reverse',
                            'route' => 'video/reverse',
                            'verb' => 'POST'
                        ],
                        # 视频变速
                        [
                            'pattern' => 'change-speed',
                            'route' => 'video/speed',
                            'verb' => 'POST'
                        ],
                        # 视频压缩
                        [
                            'pattern' => 'compress',
                            'route' => 'video/compress',
                            'verb' => 'POST'
                        ],
                        # 视频修改md5值
                        [
                            'pattern' => 'change-md5',
                            'route' => 'video/md5',
                            'verb' => 'POST'
                        ],
                        # 修改视频封面
                        [
                            'pattern' => 'modify-cover',
                            'route' => 'video/modify-cover',
                            'verb' => 'POST'
                        ],
                        # 加水印
                        [
                            'pattern' => 'add-watermark',
                            'route' => 'video/add-watermark',
                            'verb' => 'POST'
                        ],
                        # 去水印
                        [
                            'pattern' => 'rm-watermark',
                            'route' => 'video/rm-watermark',
                            'verb' => 'POST'
                        ],
                        # 视频去声音
                        [
                            'pattern' => 'del-audio',
                            'route' => 'video/del-audio',
                            'verb' => 'POST'
                        ],
                        # 视频处理记录
                        [
                            'pattern' => 'video-log',
                            'route' => 'video/video-log',
                            'verb' => 'GET'
                        ],
                        # 下载视频
                        [
                            'pattern' => 'video-download',
                            'route' => 'video/download',
                            'verb' => 'GET'
                        ],
                        #### 微信小程序相关 #####
                        # 微信登陆
                        [
                            'pattern' => 'wx-login',
                            'route' => 'wx/auth-login',
                            'verb' => 'POST'
                        ],
                        # 外网视频下载信息
                        [
                            'pattern' => 'abroad-v-download',
                            'route' => 'abroad-video/download',
                            'verb' => 'POST'
                        ],
                    ],
                ]),
            ]
        ],
    ],
    'params' => $params,
];
