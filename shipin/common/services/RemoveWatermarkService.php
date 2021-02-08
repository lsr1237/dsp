<?php

namespace common\services;


use common\extend\utils\IPUtils;
use common\extend\video\WangYi;
use common\models\VideoLogModel;

class RemoveWatermarkService
{

    /**
     * 获取抖音无水印链接
     * @param $url
     * @return mixed|string
     */
    public static function getDyRawUrl($url)
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $content = self::newCurlGet($url, $userAgent, 0, 1, true);
        //  https://www.iesdouyin.com/share/video/6857781762572012814/?region=CN&mid=6857781788673100551&u_code=16i12l1dm&titleType=title&timestamp=1597808216&utm_campaign=client_share&app=aweme&utm_medium=ios&tt_from=copy&utm_source=copy
        preg_match('/video\/(.*)\//', $content, $match);
        $itemId = $match[1] ?? '';
        if (!empty($itemId)) {
            $apiUrl = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=%s';
            $apiUrl = sprintf($apiUrl, $itemId);
            $content = self::newCurlGet($apiUrl, '', 1, 0, true);
            if (!empty($content)) {
                $response = json_decode($content, true);
                if (is_array($response['item_list']) && count($response['item_list']) > 0) {
                    // 获取*终的地址
                    $videoUrl = $response['item_list'][0]['video']['play_addr']['url_list'][0] ?? '';
                    if (!$videoUrl) {
                        return '';
                    }
                    $videoUrl = str_replace('playwm', 'play', $videoUrl);
                    return [
                        'video_url' => self::newCurlGet($videoUrl, $userAgent, 0, 1, true),
                        'img_url' => $response['item_list'][0]['video']['cover']['url_list'][0] ?? ''
                    ];
                }
            }
        }
        return '';
    }

    /**
     * 获取快手无水印链接
     * @param $url
     * @return mixed
     */
    public static function getKsRawUrl($url)
    {
    }

    /**
     * 获取火山小视频无水印地址
     * @param $url
     * @return string
     */
    public static function getHsRawUrl($url)
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $content = self::newCurlGet($url, $userAgent, 0, 1);
        $location = parse_url($content);
        $param = self::convertUrlQuery($location['query']);
        $itemId = $param['item_id'];
        if (!empty($itemId)) {
            $apiUrl = 'https://share.huoshan.com/api/item/info?item_id=%s';
            $apiUrl = sprintf($apiUrl, $itemId);
            $content = self::newCurlGet($apiUrl);
            $contentArr = json_decode($content, true);
            if ($contentArr['status_code'] == 0) {
                $url1 = $contentArr['data']['item_info']['url'];
                $url1 = parse_url($url1);
                $param1 = self::convertUrlQuery($url1['query']);
                $videoId = $param1['video_id'];
                $videoUrl = 'http://hotsoon.snssdk.com/hotsoon/item/video/_playback/?video_id=%s';
                $videoUrl = sprintf($videoUrl, $videoId);
                return $videoUrl;
                // return self::newCurlGet($videoUrl, $userAgent, 0, 1);
            }
        }
        return '';
    }

    /**
     * 获取西瓜视频下载地址（水印未去除）
     * @param $url
     * @return string
     */
    public static function getXgRawUrl($url)
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $location = self::newCurlGet($url, $userAgent, 0, 1);
        $arr = explode('?', $location);
        $arr = explode('/', $arr[0]);
        array_pop($arr);
        $itemId = array_pop($arr);
        if (!is_numeric($itemId)) {
            $itemId = substr($itemId, 1);
        }
        if ($itemId) {
            $videoInfoUrl = sprintf('https://m.365yg.com/i%s/info/', $itemId);
            $body = self::newCurlGet($videoInfoUrl);
            $body = json_decode($body, true);
            if ($body && $body['success']) {
                $videoId = $body['data']['video_id'] ?? '';
                $r = time();
                $urlPart = sprintf('/video/urls/v/1/toutiao/mp4/%s?r=%s', $videoId, $r);
                $s = crc32($urlPart);
                $url = sprintf('https://ib.365yg.com%s&s=%s', $urlPart, $s);
                $resp = self::newCurlGet($url, $userAgent);
                $b = json_decode($resp, true);
                if ($b && isset($b['data']['video_list']['video_1']['main_url'])) {
                    return base64_decode($b['data']['video_list']['video_1']['main_url']);
                }
            }
        }
        return '';

    }

    /**
     * 获取趣头条小视频无水印地址
     * @param $url
     * @return string
     */
    public static function getQttRawUrl($url)
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $urlInfo = get_headers($url, 1);
        if (isset($urlInfo['Location'])) {
            $location = $urlInfo['Location'];
            $query = parse_url($location);
            $param = self::convertUrlQuery($query['query']);
            if (isset($param['content_id'])) {
                $apiUrl = sprintf('http://html2.qktoutiao.com/detail/jsonp/1550833/15508328/155083274/%s.js', $param['content_id']);
                $content1 = self::newCurlGet($apiUrl, $userAgent);
                $contentArr = json_decode(substr($content1, 3, -1), true);
                $detail = json_decode(stripslashes($contentArr['detail']), true);
                if (isset($detail['address'][0]['url'])) {
                    return sprintf('http://v4.qutoutiao.net/%s', $detail['address'][0]['url']);
                }
            }
        }
        return '';
    }

    /**
     * 获取vue vlog小视频无水印地址
     * @param $url
     * @return array
     */
    public static function getVueVlogUrl($url)
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $content = self::newCurlGet($url, $userAgent);
        preg_match('/<video src="(.*)" poster/', $content, $match);
        $videoUrl = $match[1] ?? '';
        preg_match('/poster="(.*)" webkit/', $content, $match);
        $imgUrl = $match[1] ?? '';
        return [
            'video_url' => $videoUrl ?? '',
            'img_url' => $imgUrl ?? ''
        ];
    }

    /**
     * 获取最右小视频无水印地址
     * @param $url
     * @return array|string
     */
    public static function zuiyou($url)
    {
        $header = [
            'Host: share.izuiyou.com',
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1 Edg/84.0.4147.105'
        ];
        $ret = CurlService::sendRequest($url, '', $header);
        preg_match('/window.APP_INITIAL_STATE=(.*?)<\/script>/', $ret, $m);
        if (!isset($m[1])) {
            return '';
        }
        $jsonStr = str_replace(['new Date(', '),'], ['', ','], $m[1]);
        $json = json_decode($jsonStr, true);
        if (isset($json['sharePost']['postDetail']['post']['imgs'][0]['urls'])) {
            $img = array_shift($json['sharePost']['postDetail']['post']['imgs'][0]['urls']);
            $imgUrl = $img['urls'][0] ?? '';
        }
        if (isset($json['sharePost']['postDetail']['post']['videos'])) {
            $video = array_shift($json['sharePost']['postDetail']['post']['videos']);
            $videoUrl = $video['url'] ?? '';
        }
        return [
            'video_url' => $videoUrl ?? '',
            'img_url' => $imgUrl ?? '',
        ];
    }

    /**
     * 获取微视小视频无水印地址
     * @param $url
     * @return string
     */
    public static function getWsRawUrl($url)
    {
        $urlInfo = parse_url($url);
        $queryArr = explode('/', $urlInfo['path']);
        $feed = $queryArr[3];
        $apiUrl = 'https://h5.weishi.qq.com/webapp/json/weishi/WSH5GetPlayPage';
        $apiUrl = sprintf('%s?feedid=%s', $apiUrl, $feed);
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $content = self::newCurlGet($apiUrl, $userAgent);
        $arr = json_decode($content, true);
        if (isset($arr['data']['feeds'][0]['video_url'])) {
            return $arr['data']['feeds'][0]['video_url'];
        }
        return '';
    }

    /**
     * 获取皮皮虾小视频无水印地址
     * @param $url
     * @return string
     */
    public static function getPpxRawUrl($url)
    {
        if (strstr($url, 'pipix.com')) {
            $header = get_headers($url, 1);
            if (isset($header['location'])) {
                $url = $header['location'];
            }
            $urlInfo = parse_url($url);
            $path = explode('/', $urlInfo['path']);
            $id = $path[2];
            $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1';
            $apiUrl = 'https://h5.pipix.com/bds/webapi/item/detail/?item_id=' . $id;
            $content = self::newCurlGet($apiUrl, $userAgent);
            $arr = json_decode($content, true);
            $videoUrl = $arr['data']['item']['origin_video_download']['url_list']['0']['url'];
            return $videoUrl;
        }
        return '';
    }

    /**
     * 获取小红书小视频无水印地址
     * @param $url
     * @return array|string
     */
    public static function xiaohongshu($url)
    {
        $userAgent = "Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.119 Mobile Safari/537.36";
        $header = [
            'User-Agent:' . $userAgent,
        ];
        $content = CurlService::sendRequest($url, '', $header);
        if (preg_match('|window.__INITIAL_SSR_STATE__=(.*?)\<\/script\>|', $content, $match)) {
            $str = str_replace('undefined', '""', $match[1]);
            $result = json_decode($str, true);
            //  针对视频解析
            if ($result['NoteView']['noteType'] == 'video') {
                $videoUrl = $result['NoteView']['content']['video']['url'];
                $imgUrl = $result['NoteView']['content']['cover']['url'];
            } else {
                return '';
            }
        }
        return [
            'video_url' => $videoUrl ?? '',
            'img_url' => isset($imgUrl) && !empty($imgUrl) ? 'https:' . $imgUrl . '.jpg' : '',
        ];
    }

    /**
     * 获取映客小视频无水印地址
     * @param $url
     * @return array
     */
    public static function getYingKeUrl($url)
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36';
        $urlArr = [];
        preg_match('/feed_id=(.*)&uid=(.*)/', $url, $urlArr);
        $api = 'https://service.inke.cn/api/v2/feed/show?';
        $content = self::newCurlGet($api . $urlArr[0], $userAgent);
        $contentArr = json_decode($content, true);
        $imgUrl = $contentArr['data']['feed_info']['content']['attachments'][0]['data']['cover'];
        $videoUrl = $contentArr['data']['feed_info']['content']['attachments'][0]['data']['url'];
        return [
            'video_url' => $videoUrl ?? '',
            'img_url' => $imgUrl ?? ''
        ];
    }

    /**
     * 解析url中参数信息，返回参数数组
     */
    private static function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = [];
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    /**
     * 发送请求
     * @param string $url
     * @param string $userAgent
     * @param int $follow
     * @param int $respHeader
     * @param bool $ip
     * @return bool|mixed
     */
    private static function newCurlGet($url = '', $userAgent = '', $follow = 1, $respHeader = 0, $ip = false)
    {
        // 初始化参数
        if (empty($url)) return false;
        // 解析url地址
        $urlInfo = parse_url($url);
        $host = $urlInfo['host'];
        $referer = $urlInfo['scheme'] . '://' . $host;
        // 构造header头信息
        $header = [
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language:zh-CN,zh;q=0.8,en;q=0.6,ja;q=0.4,zh-TW;q=0.2',
            'User-Agent:' . $userAgent,
            'Host:' . $host,
            'Referer:' . $referer,
        ];
        if ($ip) {
            $ip = IPUtils::getUserIP();
            $header['CLIENT-IP'] = $ip;
            $header['X-FORWARDED-FOR'] = $ip;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate,sdch");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        if (strtolower($urlInfo["scheme"]) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if ($respHeader) {
            curl_setopt($ch, CURLOPT_HEADER, true); // 获取头部信息
            $str = curl_exec($ch);
            $length = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headerArr = explode("\r\n", substr($str, 0, $length));
            foreach ($headerArr as $val) {
                preg_match('/location:(.*)/', $val, $match);
                if ($match) {
                    return $match[1];
                }
            }
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 网易云音乐
     * https://y.music.163.com/m/mv?id=10880673&userid=1408507697&app_version=7.3.25
     * https://y.music.163.com/m/video?id=23C488DB68FAFE207C0E17D39131CB1B&userid=1408507697&app_version=7.3.25
     * @param $url
     * @return array|string
     */
    public static function wangyi($url)
    {
        $urlArr = parse_url($url);
        $query = WangYi::convertUrlArray($urlArr['query']);
        $vid = $query['id'];
        if (!$vid) {
            return '';
        }
        if ($urlArr['path'] == '/m/mv') {
            $api = 'https://interface.music.163.com/weapi/song/enhance/play/mv/url';
            $text = '{"id":' . floatval($vid) . ', "r": "1080", "csrf_token": ""}';
        } elseif ($urlArr['path'] == '/m/video') {
            $api = 'https://interface.music.163.com/weapi/cloudvideo/playurl';
            $text = '{"ids":"[\"' . $vid . '\"]","resolution":"720","csrf_token":""}';
        } else {
            return '';
        }
        $data = WangYi::createParam($text);
        $data = http_build_query($data);
        $rep = WangYi::curl($api, $data);
        $rep = json_decode($rep, true);
        if ($rep && isset($rep['data']['url'])) {
            $videoUrl = $rep['data']['url'];
        } elseif ($rep && isset($rep['urls'][0]['url'])){
            $videoUrl = $rep['urls'][0]['url'];
        } else {
            return '';
        }
        $html = CurlService::sendRequest($url);
        if ($html) {
            preg_match('/"images": \["(.*?)"\],/', $html, $m);
            if (isset($m[1])) {
                $imgUrl = $m[1];
            }
        }
        return [
            'video_url' => $videoUrl ?? '',
            'img_url' => $imgUrl ?? '',
        ];
    }

    /**
     * 解析失败记录
     * @param $txt
     * @param $userId
     * @param $memo
     */
    public static function analysisFail($txt, $userId, $memo)
    {
        VideoLogModel::add([
            'input_txt' => $txt,
            'user_id' => $userId,
            'type' => VideoLogModel::TYPE_ANALYSIS,
            'memo' => $memo,
            'state' => VideoLogModel::STATE_ERROR]);
    }

    /**
     * http转https
     * @param $url
     * @return mixed
     */
    public static function formatterUrl($url)
    {
        $urlArr = parse_url($url);
        if (isset($urlArr['scheme']) && $urlArr['scheme'] == 'http') {
            $url = str_replace('http', 'https', $url);
        }
        return $url;
    }
}