<?php

namespace common\extend\video;


class Facebook
{
    public static function generateId($url)
    {
        $id = '';
        if (is_int($url)) {
            $id = $url;
        } elseif (preg_match('#(\d+)/?$#', $url, $matches)) {
            $id = $matches[1];
        }

        return $id;
    }

    public static function cleanStr($str)
    {
        return html_entity_decode(strip_tags($str), ENT_QUOTES, 'UTF-8');
    }

    public static function getSDLink($curl_content)
    {
        $regexRateLimit = '/sd_src_no_ratelimit:"([^"]+)"/';
        $regexSrc = '/sd_src:"([^"]+)"/';
        if (preg_match($regexRateLimit, $curl_content, $match)) {
            return $match[1];
        } elseif (preg_match($regexSrc, $curl_content, $match)) {
            return $match[1];
        } else {
            return false;
        }
    }

    public static function getHDLink($curl_content)
    {
        $regexRateLimit = '/hd_src_no_ratelimit:"([^"]+)"/';
        $regexSrc = '/hd_src:"([^"]+)"/';
        if (preg_match($regexRateLimit, $curl_content, $match)) {
            return $match[1];
        } elseif (preg_match($regexSrc, $curl_content, $match)) {
            return $match[1];
        } else {
            return false;
        }
    }

    public static function getTitle($curl_content)
    {
        $title = null;
        if (preg_match('/h2 class="uiHeaderTitle"?[^>]+>(.+?)<\/h2>/', $curl_content, $matches)) {
            $title = $matches[1];
        } elseif (preg_match('/title id="pageTitle">(.+?)<\/title>/', $curl_content, $matches)) {
            $title = $matches[1];
        }

        return self::cleanStr($title);
    }

    public static function getDescription($curl_content)
    {
        if (preg_match('/span class="hasCaption">(.+?)<\/span>/', $curl_content, $matches)) {
            return self::cleanStr($matches[1]);
        }

        return false;
    }
}