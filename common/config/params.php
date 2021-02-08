<?php
return [
    'ali_notify' => 'https://sp-api.onepieces.cn/1/ali-callback', // 购买会员回调地址
    'wx_notify' => 'https://sp-api.onepieces.cn/1/wx-callback', // 微信购买会员回调地址
    'wx_refund_notify' => 'https://sp-api.onepieces.cn/1/wx-refund-callback', // 微信退款回调地址
    'img_prefix' => 'https://sp-api.onepieces.cn', // 图片前缀
    'page_limit' => 20,
    'ffmpeg_path' => [
        'ffmpeg.binaries'  => '/usr/local/bin/ffmpeg',
        'ffprobe.binaries' => '/usr/local/bin/ffprobe',
        'timeout'          => 3600, // The timeout for the underlying process
        'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
    ],
    'video_upload_path' => '/data/video', // 视频上传地址
    'video_save_path' => '/data/video/save', // 视频保存地址
    'img_upload_path' => '/data/img', // 图片保存地址
    'img_save_path' => '/data/img/save', // 图片保存地址
    'video_download_url' => 'https://sp-api.onepieces.cn', // 视频下载域名
    'jx_video_download_url' => 'https://jx-sp-api.onepieces.cn', // 视频下载域名
    'hk_video_download_url' => 'https://hk-sp-api.onepieces.cn', // 视频下载域名
    'daily_trial_times' => 1, // 每日免费次数
    'daily_trial_times_applet' => 3, // 短视频水印王
    'tik_cookies' => '/data/tik_cookies.txt',
];
