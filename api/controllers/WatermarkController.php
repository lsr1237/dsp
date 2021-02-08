<?php

namespace api\controllers;


use api\bases\ApiController;
use common\bases\CommonService;
use common\extend\utils\Utils;
use common\extend\video\src\VideoManager;
use common\extend\wx\AppletConfig;
use common\models\NumLogModel;
use common\models\VideoLogModel;
use common\services\AlapiService;
use common\services\CurlService;
use common\services\RemoveWatermarkService;
use common\services\UserService;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\helpers\Json;

class WatermarkController extends ApiController
{
    const LIMIT_SIZE = 50; // 限制视频下载大小50M
    const AB_URL_DOWNLOAD = 'https://hk-sp-api.onepieces.cn/1/abroad-v-download'; // 香港服务域名获取下载信息

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => [''],
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

    public function actionRemove()
    {
        $request = Yii::$app->request;
        $urlStr = trim($request->get('url', ''));
        $type = trim($request->get('type', 0));
        if (!$urlStr) {
            return self::err('请输入正确连接');
        }
        $appletType = (int)$request->get('applet_type', 0);
        if (!in_array($appletType, [0, AppletConfig::APPLET_ONE, AppletConfig::APPLET_TWO, AppletConfig::APPLET_THREE, AppletConfig::APPLET_FIVE])) {
            return self::err('小程序类型错误');
        }
        $user = Yii::$app->user->identity;
        $isMember = UserService::isMember($user);
        if (!$isMember && $user->num <= 0) {
            return self::err('您的次数已用完，观看广告免费下载');
        }
        $urlArr = [];
        preg_match_all('/https?:\/\/[\w-~.:%#=&?\/\\\]+/i', $urlStr, $urlArr);
        $url = $urlArr[0][0] ?? '';
        $lockKeyUser = sprintf('%s_%s', self::KEY_USER, $user->id);
        try {
            $mutex = Yii::$app->mutex;
            $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
        } catch (Exception $e) {
            Yii::error('Redis服务异常：' . $e->getMessage());
            CommonService::sendDingMsg('Redis服务异常');
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
        }
        $isOverSize = false;
        $isAb = false;
        if ($lockUser) {
            try {
                if (strstr($url, 'huoshan.com')) { // 火山
                    $return = VideoManager::HuoShan()->start($url);
                } elseif (strstr($url, 'weishi.qq.com')) { // 微视
                    $return = VideoManager::WeiShi()->start($url);
                } elseif (strstr($url, '3qtt.cn')) { // 趣头条
                    $return['video_url'] = RemoveWatermarkService::getQttRawUrl($url);
                } elseif (strstr($url, 'douyin.com')) { // 抖音
                    $return = VideoManager::DouYin()->start($url);
                } elseif (strstr($url, 'kuaishou.com') || strstr($url, 'kuaishouapp.com')) { // 快手
                    $return = VideoManager::KuaiShou()->start($url);
                } elseif (strstr($url, 'izuiyou.com')) { // 最右
                    $return = RemoveWatermarkService::zuiyou($url);
                } elseif (strstr($url, 'meipai.com')) { // 美拍
                    $return = VideoManager::MeiPai()->start($url);
                    $return['video_url'] = str_replace('//', '', $return['video_url'] ?? '');
                    if (!strstr($url, 'https://')) {
                        $return['video_url'] = sprintf('https://%s', $return['video_url']);
                    }
                } elseif (strstr($url, 'pearvideo.com')) { // 梨视频
                    $return = VideoManager::LiVideo()->start($url);
                } elseif (strstr($url, 'longxia.music.xiaomi.com')) { // 全民搞笑
                    $return = VideoManager::QuanMingGaoXiao()->start($url);
                } elseif (strstr($url, 'ippzone.com')) { // 皮皮搞笑
                    $return = VideoManager::PiPiGaoXiao()->start($url);
                } elseif (strstr($url, 'immomo.com')) { // 陌陌
                    $return = VideoManager::MoMo()->start($url);
                } elseif (strstr($url, 'shua8cn.com')) { // 刷宝
                    $return = VideoManager::ShuaBao()->start($url);
                } elseif (strstr($url, 'b23.tv') || strstr($url, 'bilibili.com')) { // bili
                    $return = VideoManager::Bili()->start($url);
                } elseif (strstr($url, 'video.weibo.com')) { // 微博
                    $return = VideoManager::WeiBo()->newVideoStart($url);
                } elseif (strstr($url, 'weibo.com') || strstr($url, 'weibo.cn')) { // 微博2
                    $return = VideoManager::WeiBo()->start($url);
                } elseif (strstr($url, 'v.qq.com')) { // 腾讯
                    $return = VideoManager::QQVideo()->start($url);
                } elseif (strstr($url, 'miaopai.com')) { // 秒拍
                    $return = VideoManager::MiaoPai()->start($url);
                } elseif (strstr($url, 'toutiaoimg.cn')
                    || strstr($url, 'toutiao.com')
                    || strstr($url, 'v.ixigua.com')) { // 头条
                    if (strstr($url, 'toutiao.com')) {
                        $return = VideoManager::TouTiao()->start($url);
                    } else {
                        $alApi = AlapiService::getVideoByUrl($url);
                        if ($alApi) {
                            $return = [
                                'video_url' => $alApi['video_url'] ?? '',
                                'img_url' => $alApi['cover_url'] ?? '',
                            ];
                        } else {
                            $return = VideoManager::TouTiao()->start($url);
                        }
                    }

                } elseif (strstr($url, 'ixigua.com')) { // 西瓜
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = VideoManager::XiGua()->start($url);
                    }
                } elseif (strstr($url, 'pipix.com')) { // 皮皮虾
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = VideoManager::PiPiXia()->start($url);
                    }
                } elseif (strstr($url, 'xiaokaxiu.com')) { // 小咖秀
                    $return = VideoManager::XiaoKaXiu()->start($url);
                } elseif (strstr($url, 'vuevideo.net')) { // Vue Vlog
                    $return = RemoveWatermarkService::getVueVlogUrl($url);
                } elseif (strstr($url, 'xhslink.com')) { // 小红书
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = RemoveWatermarkService::xiaohongshu($url);
                    }
                } elseif (strstr($url, 'inke.cn')) { // 映客
                    $return = RemoveWatermarkService::getYingKeUrl($url);
                } elseif (strstr($url, 'ulikecam.com')) { // 剪映
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = [];
                    }
                } elseif (strstr($url, 'music.163.com')) {
                    $return = RemoveWatermarkService::wangyi($url);
                } elseif (strstr($url, 'instagram.com')
                    || strstr($url, 'youtube.com')
                    || strstr($url, 'youtu.be')
                    || strstr($url, 'tumblr.com')
                    || strstr($url, 'facebook.com')
                    || strstr($url, 'tiktok.com')
                    || strstr($url, 'twitter.com')) {
                    $isAb = true;
                    $ret = CurlService::sendRequest(self::AB_URL_DOWNLOAD, ['url' => $url]);
                    $ret = json_decode($ret, true);
                    if (!$ret) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id, '系统异常');
                        return self::err('系统异常，请稍后再试');
                    }
                    if (isset($ret['status']) && $ret['status'] != self::STATUS_SUCCESS) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id, $ret['error_message'] ?? '获取视频失败');
                        return self::err($ret['error_message'] ?? '获取视频失败');
                    }
                    $return = $ret['results'] ?? '';
                } else {
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, '不支持该平台视频解析');
                    return self::err('不支持该平台视频解析');
                }
                $oriVideo = $returnUrl = $return['video_url'] ?? '';
                $oriCover = $returnImg = $return['img_url'] ?? '';
                if (!isset($returnUrl)) {
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, '不支持该平台视频解析');
                    return self::err('请输入正确链接，仅支持火山、抖音、趣头条、微视、快手、美拍、全民搞笑、陌陌、微博、刷宝、秒拍、腾讯、Instagram、YouTube、Facebook、Twitter、Tumblr短视频链接');
                }
                if (empty($returnUrl)) {
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, '无法解析该视频');
                    return self::err('请输入正确连接，稍后重试');
                }
                // tiktok直接返回下载地址
                if (strstr($url, 'tiktok.com')) {
                    $isAb = false;
                }
                if (!$isAb && $type) {
                    // 判断视频大小不超过50M，防止内存溢出
                    $header = get_headers($returnUrl, true);
                    if (!isset($header['Content-Length'])) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id, '无法解析该视频');
                        return self::err('无法解析该视频');
                    }
                    $size = $header['Content-Length'];
                    if (is_array($size)) {
                        $size = array_pop($size);
                    }
                    if ($size / 1024 / 1024 > self::LIMIT_SIZE) {
                        $isOverSize = true;
//                        $mutex->release($lockKeyUser); // 释放锁
//                        RemoveWatermarkService::analysisFail($url, $user->id, sprintf('视频大小超过%sM', self::LIMIT_SIZE));
//                        return self::err(sprintf('视频大小超过%sM，请查看下方常见问题或联系人工在线客服', self::LIMIT_SIZE));
                    } else { // 视频大小小于50M可以下载到服务下发预览
                        $videoName = Utils::create_uuid('short_') . '.mp4';
                        $savePath = Yii::$app->params['video_upload_path'] . '/' . date('Ymd') . '/' . $videoName;
                        if (!is_dir(dirname($savePath))) {
                            mkdir(dirname($savePath));
                        }
                        $video = CurlService::sendRequest(trim($returnUrl));
                        if (!$video || !file_put_contents($savePath, $video)) {
                            $mutex->release($lockKeyUser); // 释放锁
                            RemoveWatermarkService::analysisFail($url, $user->id,'视频保存失败');
                            return self::err('视频保存失败，请稍后重试');
                        }
                        $returnUrl = Yii::$app->params['jx_video_download_url'] . $savePath;
                        if ($returnImg) {
                            $imgName = Utils::create_uuid('short_') . '.jpeg';
                            $imgSavePath = Yii::$app->params['video_upload_path'] . '/' . date('Ymd') . '/' . $imgName;
                            $img = CurlService::sendRequest(trim($returnImg));
                            if (!file_put_contents($imgSavePath, $img)) {
                                $mutex->release($lockKeyUser); // 释放锁
                                RemoveWatermarkService::analysisFail($url, $user->id,'视频封面保存失败');
                                return self::err('视频封面保存失败，请稍后重试');
                            }
                            $returnImg = Yii::$app->params['jx_video_download_url'] . $imgSavePath;
                        }
                    }
                }
                $isExec = UserService::isExec($user);
                if ($isExec['status'] != self::STATUS_SUCCESS) {
                    if ($isExec['error_message'] == '观看广告免费下载') {
                        $isExec['error_message'] = '您的次数已用完，观看广告免费下载';
                    }
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, $isExec['error_message']);
                    return Json::encode($isExec);
                }
                $mutex->release($lockKeyUser); // 释放锁
                if ($type) {
                    // 次数记录
                    NumLogModel::add(['user_id' => $user->id, 'num' => 1, 'type' => NumLogModel::TYPE_RM_WATERMARK]);
                    // 解析记录
                    VideoLogModel::add([
                        'input_txt' => $url,
                        'user_id' => $user->id,
                        'ori_path' => $isOverSize ? '' : $returnImg,
                        'save_path' => $isOverSize ? '' : $returnUrl,
                        'ori_cover' => $oriCover,
                        'ori_video' => $oriVideo,
                        'type' => VideoLogModel::TYPE_ANALYSIS,
                        'state' => VideoLogModel::STATE_PAID]);
                }
                return self::success(['results' =>
                    [
                        'oversize' => $isOverSize,
                        'msg' => $isOverSize ? sprintf('视频大小超过%sM，请复制下载地址至浏览器中下载', self::LIMIT_SIZE) : '',
                        'url' => $isOverSize ? '' : $returnUrl,
                        'img_url' => $isOverSize ? '' : $returnImg,
                        'ori_cover' => $oriCover,
                        'ori_video' => $oriVideo,
                    ]
                ]);
            } catch (ErrorException $e) {
                Yii::error($e, 'video');
                $mutex->release($lockKeyUser); // 释放锁
                RemoveWatermarkService::analysisFail($url, $user->id, '解析失败');
                return self::err('解析失败，请联系人工客服');
            } catch (Exception $e) {
                Yii::error($e, 'video');
                $mutex->release($lockKeyUser); // 释放锁
                RemoveWatermarkService::analysisFail($url, $user->id, '解析失败');
                return self::err('解析失败，请联系人工客服');
            } catch (\common\extend\video\src\Exception\Exception $e) {
                Yii::error($e, 'video');
                $mutex->release($lockKeyUser); // 释放锁
                RemoveWatermarkService::analysisFail($url, $user->id, '解析失败');
                return self::err('解析失败，请联系人工客服');
            }
        }
        return self::err('系统繁忙，请稍后再试');
    }

    /**
     * 新去水印
     * @return string
     */
    public function actionRemoveN()
    {
        $request = Yii::$app->request;
        $urlStr = trim($request->get('url', ''));
        $type = trim($request->get('type', 0));
        if (!$urlStr) {
            return self::err('请输入正确连接');
        }
        $appletType = (int)$request->get('applet_type', 0);
        if (!in_array($appletType, [0, AppletConfig::APPLET_ONE, AppletConfig::APPLET_TWO, AppletConfig::APPLET_THREE, AppletConfig::APPLET_FIVE])) {
            return self::err('小程序类型错误');
        }
        $user = Yii::$app->user->identity;
//        $isMember = UserService::isMember($user);
//        if (!$isMember && $user->num <= 0) {
//            return self::err('您的次数已用完，观看广告免费下载');
//        }
        $urlArr = [];
        preg_match_all('/https?:\/\/[\w-~.:%#=&?\/\\\]+/i', $urlStr, $urlArr);
        $url = $urlArr[0][0] ?? '';
        $lockKeyUser = sprintf('%s_%s', self::KEY_USER, $user->id);
        try {
            $mutex = Yii::$app->mutex;
            $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
        } catch (Exception $e) {
            Yii::error('Redis服务异常：' . $e->getMessage());
            CommonService::sendDingMsg('Redis服务异常');
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
        }
        $isOverSize = false;
        $isAb = false;
        if ($lockUser) {
            try {
                if (strstr($url, 'huoshan.com')) { // 火山
                    $return = VideoManager::HuoShan()->start($url);
                } elseif (strstr($url, 'weishi.qq.com')) { // 微视
                    $return = VideoManager::WeiShi()->start($url);
                } elseif (strstr($url, '3qtt.cn')) { // 趣头条
                    $return['video_url'] = RemoveWatermarkService::getQttRawUrl($url);
                } elseif (strstr($url, 'douyin.com')) { // 抖音
                    $return = VideoManager::DouYin()->start($url);
//                    if (!isset($return['video_url']) || empty($return['video_url'])) {
//                        $return = RemoveWatermarkService::getDyRawUrl($url);
//                    }
                } elseif (strstr($url, 'kuaishou.com') || strstr($url, 'kuaishouapp.com')) { // 快手
                    $return = VideoManager::KuaiShou()->start($url);
                } elseif (strstr($url, 'izuiyou.com')) { // 最右
                    $return = RemoveWatermarkService::zuiyou($url);
                } elseif (strstr($url, 'meipai.com')) { // 美拍
                    $return = VideoManager::MeiPai()->start($url);
                    $return['video_url'] = str_replace('//', '', $return['video_url'] ?? '');
                    if (!strstr($url, 'https://')) {
                        $return['video_url'] = sprintf('https://%s', $return['video_url']);
                    }
                } elseif (strstr($url, 'pearvideo.com')) { // 梨视频
                    $return = VideoManager::LiVideo()->start($url);
                } elseif (strstr($url, 'longxia.music.xiaomi.com')) { // 全民搞笑
                    $return = VideoManager::QuanMingGaoXiao()->start($url);
                } elseif (strstr($url, 'ippzone.com')) { // 皮皮搞笑
                    $return = VideoManager::PiPiGaoXiao()->start($url);
                } elseif (strstr($url, 'immomo.com')) { // 陌陌
                    $return = VideoManager::MoMo()->start($url);
                } elseif (strstr($url, 'shua8cn.com')) { // 刷宝
                    $return = VideoManager::ShuaBao()->start($url);
                } elseif (strstr($url, 'b23.tv') || strstr($url, 'bilibili.com')) { // bili
                    $return = VideoManager::Bili()->start($url);
                } elseif (strstr($url, 'video.weibo.com')) { // 微博
                    $return = VideoManager::WeiBo()->newVideoStart($url);
                } elseif (strstr($url, 'weibo.com') || strstr($url, 'weibo.cn')) { // 微博2
                    $return = VideoManager::WeiBo()->start($url);
                } elseif (strstr($url, 'v.qq.com')) { // 腾讯
                    $return = VideoManager::QQVideo()->start($url);
                } elseif (strstr($url, 'miaopai.com')) { // 秒拍
                    $return = VideoManager::MiaoPai()->start($url);
                } elseif (strstr($url, 'toutiaoimg.cn')
                    || strstr($url, 'toutiao.com')
                    || strstr($url, 'v.ixigua.com')) { // 头条
                    if (strstr($url, 'toutiao.com')) {
                        $return = VideoManager::TouTiao()->start($url);
                    } else {
                        $alApi = AlapiService::getVideoByUrl($url);
                        if ($alApi) {
                            $return = [
                                'video_url' => $alApi['video_url'] ?? '',
                                'img_url' => $alApi['cover_url'] ?? '',
                            ];
                        } else {
                            $return = VideoManager::TouTiao()->start($url);
                        }
                    }

                } elseif (strstr($url, 'ixigua.com')) { // 西瓜
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = VideoManager::XiGua()->start($url);
                    }
                } elseif (strstr($url, 'pipix.com')) { // 皮皮虾
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = VideoManager::PiPiXia()->start($url);
                    }
                } elseif (strstr($url, 'xiaokaxiu.com')) { // 小咖秀
                    $return = VideoManager::XiaoKaXiu()->start($url);
                } elseif (strstr($url, 'vuevideo.net')) { // Vue Vlog
                    $return = RemoveWatermarkService::getVueVlogUrl($url);
                } elseif (strstr($url, 'xhslink.com')) { // 小红书
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = RemoveWatermarkService::xiaohongshu($url);
                    }
                } elseif (strstr($url, 'inke.cn')) { // 映客
                    $return = RemoveWatermarkService::getYingKeUrl($url);
                } elseif (strstr($url, 'ulikecam.com')) { // 剪映
                    $alApi = AlapiService::getVideoByUrl($url);
                    if ($alApi) {
                        $return = [
                            'video_url' => $alApi['video_url'] ?? '',
                            'img_url' => $alApi['cover_url'] ?? '',
                        ];
                    } else {
                        $return = [];
                    }
                } elseif (strstr($url, 'music.163.com')) {
                    $return = RemoveWatermarkService::wangyi($url);
                } elseif (strstr($url, 'instagram.com')
                    || strstr($url, 'youtube.com')
                    || strstr($url, 'youtu.be')
                    || strstr($url, 'tumblr.com')
                    || strstr($url, 'facebook.com')
                    || strstr($url, 'tiktok.com')
                    || strstr($url, 'twitter.com')) {
                    $isAb = true;
                    $ret = CurlService::sendRequest(self::AB_URL_DOWNLOAD, ['url' => $url]);
                    $ret = json_decode($ret, true);
                    if (!$ret) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id, '系统异常');
                        return self::err('系统异常，请稍后再试');
                    }
                    if (isset($ret['status']) && $ret['status'] != self::STATUS_SUCCESS) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id, $ret['error_message'] ?? '获取视频失败');
                        return self::err($ret['error_message'] ?? '获取视频失败');
                    }
                    $return = $ret['results'] ?? '';
                } else {
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, '不支持该平台视频解析');
                    return self::err('不支持该平台视频解析');
                }
                $oriVideo = $returnUrl = $return['video_url'] ?? '';
                $oriCover = $returnImg = $return['img_url'] ?? '';
                if (!isset($returnUrl)) {
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, '不支持该平台视频解析');
                    return self::err('请输入正确链接，仅支持火山、抖音、趣头条、微视、快手、美拍、全民搞笑、陌陌、微博、刷宝、秒拍、腾讯、Instagram、YouTube、Facebook、Twitter、Tumblr短视频链接');
                }
                if (empty($returnUrl)) {
                    $mutex->release($lockKeyUser); // 释放锁
                    RemoveWatermarkService::analysisFail($url, $user->id, '无法解析该视频');
                    return self::err('请输入正确连接，稍后重试');
                }
                // tiktok直接返回下载地址
                if (strstr($url, 'tiktok.com')) {
                    $isAb = false;
                }
                if (!$isAb && $type) {
                    // 判断视频大小不超过50M，防止内存溢出
                    $header = get_headers($returnUrl, true);
                    if (!isset($header['Content-Length'])) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id, '无法解析该视频');
                        return self::err('无法解析该视频');
                    }
                    $size = $header['Content-Length'];
                    if (is_array($size)) {
                        $size = array_pop($size);
                    }
                    if ($size / 1024 / 1024 > self::LIMIT_SIZE) {
                        $isOverSize = true;
//                        $mutex->release($lockKeyUser); // 释放锁
//                        RemoveWatermarkService::analysisFail($url, $user->id, sprintf('视频大小超过%sM', self::LIMIT_SIZE));
//                        return self::err(sprintf('视频大小超过%sM，请查看下方常见问题或联系人工在线客服', self::LIMIT_SIZE));
                    } // else { // 视频大小小于50M可以下载到服务下发预览
//                        $videoName = Utils::create_uuid('short_') . '.mp4';
//                        $savePath = Yii::$app->params['video_upload_path'] . '/' . date('Ymd') . '/' . $videoName;
//                        if (!is_dir(dirname($savePath))) {
//                            mkdir(dirname($savePath));
//                        }
//                        $video = CurlService::sendRequest(trim($returnUrl));
//                        if (!$video || !file_put_contents($savePath, $video)) {
//                            $mutex->release($lockKeyUser); // 释放锁
//                            RemoveWatermarkService::analysisFail($url, $user->id,'视频保存失败');
//                            return self::err('视频保存失败，请稍后重试');
//                        }
//                        $returnUrl = Yii::$app->params['jx_video_download_url'] . $savePath;
//                        if ($returnImg) {
//                            $imgName = Utils::create_uuid('short_') . '.jpeg';
//                            $imgSavePath = Yii::$app->params['video_upload_path'] . '/' . date('Ymd') . '/' . $imgName;
//                            $img = CurlService::sendRequest(trim($returnImg));
//                            if (!file_put_contents($imgSavePath, $img)) {
//                                $mutex->release($lockKeyUser); // 释放锁
//                                RemoveWatermarkService::analysisFail($url, $user->id,'视频封面保存失败');
//                                return self::err('视频封面保存失败，请稍后重试');
//                            }
//                            $returnImg = Yii::$app->params['jx_video_download_url'] . $imgSavePath;
//                        }
//                     }
                }
//                $isExec = UserService::isExec($user);
//                if ($isExec['status'] != self::STATUS_SUCCESS) {
//                    $mutex->release($lockKeyUser); // 释放锁
//                    RemoveWatermarkService::analysisFail($url, $user->id, $isExec['error_message']);
//                    return Json::encode($isExec);
//                }
                $mutex->release($lockKeyUser); // 释放锁
                if ($type) {
                    // 次数记录
                    // NumLogModel::add(['user_id' => $user->id, 'num' => 1, 'type' => NumLogModel::TYPE_RM_WATERMARK]);
                    // 解析记录
                    $log = VideoLogModel::add([
                        'input_txt' => $url,
                        'user_id' => $user->id,
                        'ori_path' => $isOverSize ? '' : $returnImg,
                        'save_path' => $isOverSize ? '' : $returnUrl,
                        'ori_cover' => $oriCover,
                        'ori_video' => $oriVideo,
                        'state' => VideoLogModel::STATE_SUCCESS,
                        'type' => VideoLogModel::TYPE_ANALYSIS]);
                    if (!$log) {
                        $mutex->release($lockKeyUser); // 释放锁
                        RemoveWatermarkService::analysisFail($url, $user->id,'解析记录保存失败');
                        return self::err('解析记录保存失败，请稍后重试');
                    }
                }
                return self::success(['results' =>
                    [
                        'oversize' => $isOverSize,
                        'msg' => $isOverSize ? sprintf('视频大小超过%sM，请复制下载地址至浏览器中下载', self::LIMIT_SIZE) : '',
                        'url' => $isOverSize ? '' : RemoveWatermarkService::formatterUrl($returnUrl),
                        'id' => $log->id ?? '',
//                        'img_url' => $isOverSize ? '' : $returnImg,
//                        'ori_cover' => $oriCover,
//                        'ori_video' => $oriVideo,
                    ]
                ]);
            } catch (ErrorException $e) {
                Yii::error($e, 'video');
                $mutex->release($lockKeyUser); // 释放锁
                RemoveWatermarkService::analysisFail($url, $user->id, '解析失败');
                return self::err('解析失败，请联系人工客服');
            } catch (Exception $e) {
                Yii::error($e, 'video');
                $mutex->release($lockKeyUser); // 释放锁
                RemoveWatermarkService::analysisFail($url, $user->id, '解析失败');
                return self::err('解析失败，请联系人工客服');
            } catch (\common\extend\video\src\Exception\Exception $e) {
                Yii::error($e, 'video');
                $mutex->release($lockKeyUser); // 释放锁
                RemoveWatermarkService::analysisFail($url, $user->id, '解析失败');
                return self::err('解析失败，请联系人工客服');
            }
        }
        return self::err('系统繁忙，请稍后再试');
    }

    /**
     * 获取解析记录详情
     * @return string
     */
    public function actionDetail()
    {
        $user = Yii::$app->user->identity;
        $request = Yii::$app->request;
        $id = (int)$request->get('id', 0);
//        $isMember = UserService::isMember($user);
//        if (!$isMember && $user->num <= 0) {
//            return self::err('观看广告免费下载');
//        }
        $log = VideoLogModel::findOneByCond(['id' => $id, 'user_id' => $user->id, 'type' => VideoLogModel::TYPE_ANALYSIS]);
        if (!$log) {
            return self::err('记录不存在');
        }
        if ($log->type != VideoLogModel::TYPE_ANALYSIS && !file_exists($log->save_path)) {
            return self::err('视频已过期');
        }
        if ($log->state != VideoLogModel::STATE_PAID) {
            $lockKeyUser = sprintf('%s_%s', self::KEY_USER, $user->id);
            try {
                $mutex = Yii::$app->mutex;
                $lockUser = $mutex->acquire($lockKeyUser, 0); // 用户获取锁
            } catch (Exception $e) {
                Yii::error('Redis服务异常：' . $e->getMessage());
                CommonService::sendDingMsg('Redis服务异常');
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '系统错误，请稍后再试~']);
            }
            if ($lockUser) {
                try {
                    $isExec = UserService::isExec($user);
                    if ($isExec['status'] != self::STATUS_SUCCESS) {
                        $mutex->release($lockKeyUser); // 释放锁
                        return Json::encode($isExec);
                    }
                    $mutex->release($lockKeyUser); // 释放锁
                    VideoLogModel::updateByCond(['id' => $log->id], ['state' => VideoLogModel::STATE_PAID]);
                    NumLogModel::add(['user_id' => $user->id, 'num' => 1, 'type' => NumLogModel::TYPE_RM_WATERMARK]);
                    $img = $log->ori_path;
                    $path = $log->save_path;
                    if (!$log->ori_cover) {
                        $oriCover = $img;
                    } else {
                        $oriCover = $log->ori_cover;
                    }
                    if (!$log->ori_video) {
                        $oriVideo = $path;
                    } else {
                        $oriVideo = $log->ori_video;
                    }
                    return self::success([
                        'results' => [
                            [
                                'ori_cover' => $oriCover,
                                'ori_video' => RemoveWatermarkService::formatterUrl($oriVideo),
                                'url' =>  RemoveWatermarkService::formatterUrl($path),
                                'img_url' => $img,
                            ]
                        ],
                    ]);
                } catch (Exception $e) {
                    Yii::error($e, 'video');
                    $mutex->release($lockKeyUser); // 释放锁
                    return self::err('获取记录失败，请联系人工客服');
                }
            }
        }
        return self::err('您操作过于频繁，请稍休息会再试');
    }
}