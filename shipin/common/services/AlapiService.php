<?php

namespace common\services;


class AlapiService
{
    const TOKEN = '92eCCMijxI4Sfr2VOO45';
    const JH_VIDEO_URL = 'https://v1.alapi.cn/api/video/url';

    public static function getVideoByUrl($url)
    {
        $data = [
            'token' => self::TOKEN,
            'url' => $url
        ];
        $ret = CurlService::sendRequest(self::JH_VIDEO_URL, $data);
        if ($ret) {
            $ret = json_decode($ret, true);
            if ($ret['code'] == 200 && isset($ret['data']) && !empty($ret['data'])) {
                return $ret['data'];
            }
        }
        return false;
    }
}