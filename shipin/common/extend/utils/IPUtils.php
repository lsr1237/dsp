<?php

namespace common\extend\utils;

class IPUtils
{
    public static function getUserIP()
    {
        $ip = "unknown";
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_X_CLIENT_ADDRESS"])) {
                $ip = $_SERVER["HTTP_X_CLIENT_ADDRESS"];
            } elseif (isset($_SERVER["HTTP_CLIENT_ip"])) {
                $ip = $_SERVER["HTTP_CLIENT_ip"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_X_CLIENT_ADDRESS')) {
                $ip = getenv('HTTP_X_CLIENT_ADDRESS');
            } elseif (getenv('HTTP_CLIENT_ip')) {
                $ip = getenv('HTTP_CLIENT_ip');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }
        if (trim($ip) == "::1") {
            $ip = "127.0.0.1";
        }
        return $ip;
    }
}