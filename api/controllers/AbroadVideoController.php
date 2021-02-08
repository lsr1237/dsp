<?php

namespace api\controllers;


use api\bases\ApiController;
use common\extend\utils\Utils;
use common\services\AbroadVideoService;
use common\services\CurlService;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;

class AbroadVideoController extends ApiController
{
    const LIMIT_SIZE = 50; // 限制视频下载大小50M

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => ['download'],
                'allow' => true,
                'roles' => ['?'],
            ],
            // 其它的Action必须要授权用户才可访问
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ];
        return $behaviors;
    }

    public function actionDownload()
    {
        $request = Yii::$app->request;
        $urlStr = trim($request->post('url', ''));
        $urlArr = [];
        $dirReturn = false;
        preg_match_all('/https?:\/\/[\w-~.%#=&?\/\\\]+/i', $urlStr, $urlArr);
        $url = $urlArr[0][0] ?? '';
        if ($url) {
            try {
                if (strstr($url, 'instagram.com')) {
                    $ret = AbroadVideoService::instagram($url);
                } elseif (strstr($url, 'youtube.com') || strstr($url, 'youtu.be')) {
                    $ret = AbroadVideoService::youtube($url);
                    if (!$ret || !$ret['video_url']) {
                        $ret = AbroadVideoService::yt($url);
                    }
                } elseif (strstr($url, 'tumblr.com')) {
                    $ret = AbroadVideoService::tumblr($url);
                } elseif (strstr($url, 'facebook.com')) {
                    $ret = AbroadVideoService::facebook($url);
                } elseif (strstr($url, 'twitter.com')) {
                    $ret = AbroadVideoService::twitter($url);
                } elseif (strstr($url, 'tiktok.com')) {
                    $ret = AbroadVideoService::tiktok($url);
                    $dirReturn = true;
                } else {
                    return self::err('您的链接无法找到内容，请输入正确的视频连接');
                }
                if (!$ret || !$ret['video_url']) {
                    return self::err('获取失败');
                }
                $returnUrl = $ret['video_url'] ?? '';
                $returnImg = $ret['img_url'] ?? '';
                if ($dirReturn) {
                    return self::success(['results' => ['video_url' => $returnUrl ?? '', 'img_url' => $returnImg ?? '']]);
                }
                $header = get_headers($returnUrl, true);
                if (!isset($header['Content-Length'])) {
                    return self::err('无法解析该视频');
                }
                $size = $header['Content-Length'];
                if (is_array($size)) {
                    $size = array_pop($size);
                }
                if ($size / 1024 / 1024 > self::LIMIT_SIZE) {
                    return self::err(sprintf('视频大小超过%sM，无法获取！', self::LIMIT_SIZE));
                }
                $videoName = Utils::create_uuid('short_') . '.mp4';
                $savePath = Yii::$app->params['video_upload_path'] . '/' . date('Ymd') . '/' . $videoName;
                if (!is_dir(dirname($savePath))) {
                    mkdir(dirname($savePath), 0777, true);
                }
                $video = CurlService::sendRequest(trim($returnUrl));
                if (!$video || !file_put_contents($savePath, $video)) {
                    return self::err('视频保存失败，请稍后重试');
                }
                $returnUrl = Yii::$app->params['hk_video_download_url'] . $savePath;
                if ($returnImg) {
                    $imgName = Utils::create_uuid('short_') . '.jpeg';
                    $imgSavePath = Yii::$app->params['video_upload_path'] . '/' . date('Ymd') . '/' . $imgName;
                    $img = CurlService::sendRequest(trim($returnImg));
                    if (!$img || !file_put_contents($imgSavePath, $img)) {
                        return self::err('视频封面保存失败，请稍后重试');
                    }
                    $returnImg = Yii::$app->params['hk_video_download_url'] . $imgSavePath;
                }
                return self::success(['results' => ['video_url' => $returnUrl ?? '', 'img_url' => $returnImg ?? '']]);
            } catch (Exception $e) {
                Yii::error($e, 'video');
                return self::err('系统异常');
            } catch (ErrorException $e) {
                Yii::error($e, 'video');
                return self::err('获取失败');
            }
        }
        return self::err('请输入正确的视频连接');
    }
}