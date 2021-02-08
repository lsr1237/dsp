<?php

namespace common\services;


use common\extend\video\Facebook;
use common\extend\video\TikGet\TikGet;
use common\extend\video\yt\YouTubeDownloader;
use InstagramScraper\Instagram;
use Phpfastcache\Helper\Psr16Adapter;
use yii\base\Exception;

class AbroadVideoService
{
    const INS_USERNAME = 'wps361009';
    const INS_PASSWORD = '361009ins';

    /**
     * 获取ins视频信息
     * @param $url
     * @return array
     */
    public static function instagram($url)
    {
        $url = explode('?', $url);
        $url = $url[0];
//        Instagram::setProxy([
//            'address' => '127.0.0.1',
//            'port'    => '10809',
//            'tunnel'  => true,
//            'timeout' => 30,
//        ]);
        $instagram = Instagram::withCredentials(self::INS_USERNAME, self::INS_PASSWORD, new Psr16Adapter('Files'));
        $instagram->login(); // will use cached session if you want to force login $instagram->login(true)
        $instagram->saveSession();  //DO NOT forget this in order to save the session, otherwise have no sense
        $nonPrivateAccountMedias = $instagram->getMediaByUrl($url);
        return [
            'video_url' => $nonPrivateAccountMedias->getVideoStandardResolutionUrl(),
            'img_url' => $nonPrivateAccountMedias->getImageHighResolutionUrl()
        ];
    }

    /**
     * 获取youtube视频信息
     * @param $url
     * @return array
     * @throws Exception
     */
    public static function youtube($url)
    {
        try {
            $yt = new YouTubeDownloader();
            $links = $yt->getDownloadLinks($url, 'mp4');
            return [
                'video_url' => isset($links['url'][0]['url']) ? $links['url'][0]['url'] : '',
                'img_url' => isset($links['thumbnail']) && !empty($links['thumbnail']) ? $links['thumbnail'] : '',
            ];
        } catch (Exception $e) {
            throw new Exception('查询失败');
        }
    }

    public static function yt($url)
    {
        $api = 'https://youtube-downloader3.herokuapp.com/video_info.php?url='.$url;
        $ret = CurlService::sendRequest($api);
        if ($ret) {
            $ret = json_decode($ret, true);
            return [
                'video_url' => isset($ret['links'][0]['url']) ? $ret['links'][0]['url'] : '',
                'img_url' => ''
            ];
        }
        return '';
    }

    /**
     * facebook
     * @param $url
     * @return array
     * @throws Exception
     */
    public static function facebook($url)
    {
        try {
//            $context = [
//                'http' => [
//                    'method' => 'GET',
//                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36',
//                    'proxy' => 'tcp://127.0.0.1:10809', // 上线需删除
//                    'request_fulluri' => true // 上线需删除
//                ],
//            ];
//            $context = stream_context_create($context);
//            $data = file_get_contents($url, false, $context);
            $header = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36'];
            $data = CurlService::sendRequest($url, '', $header);
//            $msg['id'] = Facebook::generateId($url);
//            $msg['title'] = Facebook::getTitle($data);

//            if ($sdLink = Facebook::getSDLink($data)) {
//                $msg['links']['Download Low Quality'] = $sdLink;
//            }

//            if ($hdLink = Facebook::getHDLink($data)) {
//                $msg['links']['Download High Quality'] = $hdLink;
//            }
            return [
                'video_url' => Facebook::getSDLink($data) ?? '',
                'img_url' => ''
            ];
        } catch (Exception $e) {
            throw new Exception('查询失败');
        }
    }

    /**tumblr
     * @param $url
     * @return array
     */
    public static function tumblr($url)
    {
        $data = CurlService::sendRequest($url);
        preg_match('/<source src="(.*)" type/', $data, $match);
        $videoUrl = $match[1] ?? '';
        preg_match('/poster="(.*)">/', $data, $match);
        $imgUrl = $match[1] ?? '';
        return [
            'video_url' => $videoUrl ?? '',
            'img_url' => $imgUrl ?? ''
        ];
    }

    /**
     * twitter
     * @param $url
     * @return array
     */
    public static function twitter($url)
    {
        $api = 'https://www.savetweetvid.com/zh/downloader';
        $data = [
            'url' => $url,
        ];
        $ret = CurlService::sendRequest($api, $data);
        preg_match_all('/https?:\/\/[\w-~.%#=&?\/\\\]+mp4/i', $ret, $urlArr);
        preg_match_all('/https?:\/\/[\w-~.%#=&?\/\\\]+\.jpg/i', $ret, $urlArr2);
        return [
            'video_url' => $urlArr[0][0] ?? '',
            'img_url' => $urlArr2[0][0] ?? ''
        ];
    }

    /**
     * tiktok
     * @param $url
     * @return array|bool
     */
    public static function tiktok($url)
    {
        try {
            $video = new TikGet($url);
            $info = $video->get();
            $videoDownloadAddr = $video->getDownloadUrl($info->video->id) ?? '';
            return [
                'video_url' => $videoDownloadAddr ?? '',
                'img_url' => $info->video->cover ?? ''
            ];
        } catch (Exception $e) {
            \Yii::error($e);
            return false;
        }
    }
}