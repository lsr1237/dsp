<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/16
 * Time: 14:41
 */

namespace api\controllers;


use api\bases\ApiController;
use common\bases\CommonService;
use common\extend\wx\AppletConfig;
use common\models\NumLogModel;
use common\models\Uploader;
use common\extend\utils\Utils;
use common\models\UserModel;
use common\models\VideoLogModel;
use common\services\FFMpegService;
use common\services\RedisService;
use common\services\RemoveWatermarkService;
use common\services\UserService;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;

class VideoController extends ApiController
{

    const VIDEO_ACT_TYPE_DOWNLOAD = 'download'; // 下载视频
    const VIDEO_ACT_TYPE_PREVIEW = 'preview'; // 预览视频
    const LIMIT_SIZE = 50; // 限制视频下载大小50M

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

    /**
     * 上传视频
     * @return string
     */
    public function actionUploadVideo()
    {
        $userId = Yii::$app->user->getId();
        $key = sprintf('%s_%s_%s', RedisService::KEY_UPLOAD, date('Y-m-d'), $userId);
        $num = (int)RedisService::getKey($key);
        if ($num > UserModel::UPLOAD_LIMIT) {
            return self::err('今日上传次数已超过限制');
        }
        if (empty($_FILES) || !isset($_FILES['file'])) {
            return self::err('请选择视频');
        }
        if ($_FILES['file']['error'] != 0) {
            Yii::error(sprintf('视频上传失败（%s）', json_encode($_FILES)));
            return self::err('上传失败');
        }
        if ($_FILES['file']['size'] / 1024 / 1024 > self::LIMIT_SIZE) {
            return self::err(sprintf('上传视频大小超过%sM', self::LIMIT_SIZE));
        }
        $temFilePath = $_FILES['file']['tmp_name']; // 上传视频临时文件
        if (file_exists($temFilePath)) {
            $ffmpeg = new FFMpegService();
            $info = $ffmpeg->getInfo($temFilePath);
            if (!$info) {
                return self::err('上传视频不合规，请重新上传');
            }
            $newName = Utils::create_uuid('video_');
            $videoUploadPath = Yii::$app->params['video_upload_path'] ?? '';
            $date = date('Ymd');
            $config = [
                "pathFormat" => sprintf('/%s/%s', $date, $newName),
                "maxSize" => 1024 * 1024 * 50, // 小于50M未知
                "allowFiles" => ['.mp4'], // 只允许扩展名为.mp4的文件上传
                'uploadFilePath' => $videoUploadPath,
            ];
            if (Yii::$app->request->isPost) {
                $model = new Uploader('file', $config, '');
                $result = $model->getFileInfo();
                if ($result['state'] == self::STATUS_SUCCESS) {
                    if ($num == 0) {
                        RedisService::setKeyWithExpire($key, 1, RedisService::EXPIRE);
                    } else {
                        RedisService::incr($key);
                    }
                    $videoImg = str_replace([
                        'video_',
                        '.mp4'
                    ], [
                        'img_',
                        '.jpg'
                    ], $result['url']);
                    $videoUrl = $videoUploadPath . $result['url'];
                    $videoImg = Yii::$app->params['img_save_path'] . $videoImg;
                    if (!is_dir(dirname($videoImg))) {
                        mkdir(dirname($videoImg));
                    }
                    $ffmpeg->getPicture($videoUrl, 0, $videoImg);
                    return self::success(['results' => [
                        'url' => $videoUrl,
                    ]]);
                } else {
                    return self::err($result['state']);
                }
            }
        }
        return self::err('上传失败');
    }

    /**
     * 上传图片
     * @return string
     */
    public function actionUploadImg()
    {
        $userId = Yii::$app->user->getId();
        $key = sprintf('%s_%s_%s', RedisService::KEY_UPLOAD, date('Y-m-d'), $userId);
        $num = RedisService::getKey($key) ?? 0;
        if ($num > UserModel::UPLOAD_LIMIT) {
            return self::err('今日上传次数已超过限制');
        }
        if (empty($_FILES)) {
            return self::err('请选择图片');
        }
        if ($_FILES['file']['error'] != 0) {
            Yii::error(sprintf('上传失败（%s）', json_encode($_FILES)));
            return self::err('上传失败');
        }
        $temFilePath = $_FILES['file']['tmp_name']; // 上传图片临时文件
        if (file_exists($temFilePath)) {
            if (!@getimagesize($temFilePath)) {
                return self::err('请上传正确图片');
            }
            $newName = Utils::create_uuid('img_');
            $videoUploadPath = Yii::$app->params['img_upload_path'] ?? '';
            $date = date('Ymd');
            $config = [
                "pathFormat" => sprintf('/%s/%s', $date, $newName),
                "maxSize" => 1024 * 1024 * 1, // 小于2M未知
                "allowFiles" => ['.jpg', '.jpeg', '.png'],
                'uploadFilePath' => $videoUploadPath,
            ];
            if (Yii::$app->request->isPost) {
                $model = new Uploader('file', $config, '');
                $result = $model->getFileInfo();
                if ($result['state'] == self::STATUS_SUCCESS) {
                    if ($num == 0) {
                        RedisService::setKeyWithExpire($key, 1, RedisService::EXPIRE);
                    } else {
                        RedisService::incr($key);
                    }
                    return self::success(['results' => [
                        'url' => $videoUploadPath . $result['url'],
                    ]]);
                } else {
                    return self::err($result['state']);
                }
            }
        }
        return self::err('上传失败');
    }

    /**
     * 视频剪裁尺寸
     * @return string
     */
    public function actionSizeCut()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $x = floatval($request->post('x', 0)); // x坐标
        $y = floatval($request->post('y', 0)); // y坐标
        $width = floatval($request->post('width', 0));
        $height = floatval($request->post('height', 0));
        if ($width <= 0 || $height <= 0) {
            return self::err('截取尺寸错误，请重试');
        }
        $ffmpeg = new FFMpegService();
        $info = $ffmpeg->getInfo($path);
        $wVideo = $info->get('width') ?? 0;
        $hVideo = $info->get('height') ?? 0;
        if (($width + $x) > $wVideo || ($height + $y) > $hVideo) {
            return self::err('截取尺寸错误，请重试');
        }
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->sizeCutting($path, $x, $y, $width, $height, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_SIZE_CUT);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 剪裁视频长度
     * @return string
     */
    public function actionDurationCut()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $start = floatval($request->post('start', 0));
        $len = floatval($request->post('len', 0));
        if ($start <= 0 || $len <= 0) {
            return self::err('截取时长错误，请重试');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $info = $ffmpeg->getInfo($path);
        $duration = (int)$info->get('duration') ?? 0;
        if ($start >= $duration || ($start + $len) > $duration) {
            return self::err('截取时长错误，请重试');
        }
        $ret = $ffmpeg->durationCut($path, $start, $len, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_DURATION_CUT);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 获取音频
     * @return string
     */
    public function actionGetAudio()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->getAudio($path, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_GET_AUDIO);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 视频倒放
     * @return string
     */
    public function actionReverse()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->reverse($path, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_REVERSE);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 视频变速
     * @return string
     */
    public function actionSpeed()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $speed = floatval($request->post('speed', 1));
        if (!in_array($speed, FFMpegService::SPEED_ARR)) {
            return self::err('变速参数错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->speed($path, $speed, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_SPEED);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 视频压缩
     * @return string
     */
    public function actionCompress()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->compress($path, FFMpegService::LOW_QUALITY, $savePath); // 默认低品质
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_COMPRESS);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 视频修改md5值
     * @return string
     */
    public function actionMd5()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->md5($path, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_MD5);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 去除视频音频
     * @return string
     */
    public function actionDelAudio()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->delAudio($path, $savePath);
        if ($ret) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_DEL_AUDIO);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 修改视频封面
     * @return string
     */
    public function actionModifyCover()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $time = floatval($request->post('time', 0));
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $info = $ffmpeg->getInfo($path);
        $duration = (int)$info->get('duration') ?? 0;
        if ($time > $duration) {
            return self::err('时间点参数错误，请重试');
        }
        $savePicPath = str_replace([
            Yii::$app->params['video_save_path'],
            'video_',
            '.mp4'
        ], [
            Yii::$app->params['img_save_path'],
            'img_',
            '.jpg'
        ], $savePath);
        if (!is_dir(Yii::$app->params['img_save_path'])) {
            mkdir(Yii::$app->params['img_save_path']);
        }
        $ret1 = $ffmpeg->getPicture($path, $time, $savePicPath);
        if (!$ret1) {
            return self::err('图片截取失败');
        }
        $ret2 = $ffmpeg->modifyCover($path, $savePicPath, $savePath);
        if ($ret2) {
            $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_MODIFY_COVER);
            return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
        }
        return self::err('处理失败');
    }

    /**
     * 添加水印
     * @return string
     */
    public function actionAddWatermark()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        $imgPath = trim($request->post('img_path', ''));
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        if (!file_exists($imgPath)) {
            return self::err('图片上传错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $ret = $ffmpeg->addWaterMark($path, $imgPath, $savePath);
        if ($ret) {
            $add = $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_ADD_WATERMARK);
            if ($add) {
                return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
            }
        }
        return self::err('处理失败');
    }

    /**
     * 去水印
     * @return string
     */
    public function actionRmWatermark()
    {
        $userId = Yii::$app->user->getId();
        $request = Yii::$app->request;
        $path = trim($request->post('path', ''));
        $multiple = (double)$request->post('multiple', '');
        if (!file_exists($path)) {
            return self::err('视频上传错误');
        }
        $param = trim($request->post('param', ''));
        if (!$param) {
            return self::err('去水印区域信息错误');
        }
        $param = json_decode($param, true);
        if (!$param) {
            return self::err('去水印区域信息错误');
        }
        if ($multiple <= 0) {
            return self::err('参数错误');
        }
        $ffmpeg = new FFMpegService();
        $savePath = $ffmpeg->getSavePath($path);
        $info = $ffmpeg->getInfo($path);
        $wVideo = $info->get('width') ?? 0;
        $hVideo = $info->get('height') ?? 0;
        foreach ($param as &$p) {
            $x = $p['x'] =  ceil(($p['x'] ?? 0) * $multiple);
            $y = $p['y'] = ceil(($p['y'] ?? 0) * $multiple);
            $w = $p['width'] = floor(($p['width'] ?? 0) * $multiple);
            $h = $p['height'] = floor(($p['height'] ?? 0) * $multiple);
            if ($w <= 0 || $h <= 0) {
                return self::err('截取尺寸错误，请重试');
            }
            if ($x <= 0 || $y <= 0) {
                return self::err('截取坐标必须大于（0,0），请重试');
            }
            if (($w + $x) >= $wVideo || ($h + $y) >= $hVideo) {
                return self::err('水印区域超限，请重试');
            }
        }
        $ret = $ffmpeg->removeWatermarkMulti($path, $param, $savePath);
        if ($ret) {
            $add = $ffmpeg->addLog($userId, $path, $savePath, VideoLogModel::TYPE_RM_WATERMARK);
            if ($add) {
                return self::successMsg('视频已经在处理中，请在首页“剪辑记录下载”查看状态以及下载。');
            }
        }
        return self::err('处理失败');
    }

    /**
     * 获取视频列表
     * @return string
     */
    public function actionVideoLog()
    {
        $userId = Yii::$app->user->getId();
//        $request = Yii::$app->request;
//        $pn = (int)$request->get('pn', 1); // 页数
//        $limit = Yii::$app->params['page_limit'];
//        $offset = ($pn - 1) * $limit;
        $video = VideoLogModel::getList(8, 0, $userId);
        $ffmpeg = new FFMpegService();
        foreach ($video['list'] as $row) {
            if (file_exists($row->save_path)) {
                $videoInfo = $ffmpeg->getInfo($row->save_path);
                if ($videoInfo) {
                    $duration = round($videoInfo->get('duration'), 0);
                }
            }
            $duration = $duration ?? 0;
            if ($row->type == VideoLogModel::TYPE_ANALYSIS) {
                $duration = '--';
            }
            $path = Yii::$app->params['video_download_url'];
            if ($row->type == VideoLogModel::TYPE_ANALYSIS) {
                $path = RemoveWatermarkService::formatterUrl($row->save_path);
            } else {
                $path = $path . $row->save_path;
            }
            $data[] = [
                'id' => $row->id,
                'type' => $row->type,
                'duration' => $duration,
                'created_at' => $row->created_at,
                'path' => $path,
            ];
        }
        return self::success([
            'results' => $data ?? [],
            'has_more' => false,
        ]);
    }

    /**
     * 下载地址
     * @return string
     */
    public function actionDownload()
    {
        $user = Yii::$app->user->identity;
        $request = Yii::$app->request;
        $id = (int)$request->get('id', 0);
        $type = $request->get('type', self::VIDEO_ACT_TYPE_DOWNLOAD);
        if (!in_array($type, [self::VIDEO_ACT_TYPE_DOWNLOAD, self::VIDEO_ACT_TYPE_PREVIEW])) {
            return self::err('操作类型错误');
        }
        $appletType = (int)$request->get('applet_type', AppletConfig::APPLET_ONE);
        if (!in_array($appletType, AppletConfig::APPLET_ARR)) {
            return self::err('小程序类型错误');
        }
        $path = Yii::$app->params['video_download_url'];
        $log = VideoLogModel::findOneByCond(['id' => $id, 'user_id' => $user->id]);
        if (!$log) {
            return self::err('记录不存在');
        }
        if ($log->type != VideoLogModel::TYPE_ANALYSIS && !file_exists($log->save_path)) {
            return self::err('视频已过期');
        }
        if ($type == self::VIDEO_ACT_TYPE_DOWNLOAD && $log->state != VideoLogModel::STATE_PAID) {
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
                $isExec = UserService::isExec($user);
                if ($isExec['status'] != self::STATUS_SUCCESS) {
                    $mutex->release($lockKeyUser); // 释放锁
                    return Json::encode($isExec);
                }
                $mutex->release($lockKeyUser); // 释放锁
                VideoLogModel::updateByCond(['id' => $log->id], ['state' => VideoLogModel::STATE_PAID]);
                NumLogModel::add(['user_id' => $user->id, 'num' => 1, 'type' => NumLogModel::TYPE_DOWNLOAD]);
            } else {
                return self::err('系统繁忙，请稍后再试');
            }
        }
        if ($log->type == VideoLogModel::TYPE_ANALYSIS) {
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
        } else {
            $img = str_replace([
                Yii::$app->params['video_save_path'],
                'video_',
                '.mp4'
            ], [
                Yii::$app->params['img_save_path'],
                'img_',
                '.jpg'
            ], $log->save_path);
            $oriCover = $img = $path . $img;
            $oriVideo = $path = $path . $log->save_path;
        }
        return self::success([
            'results' => [
                [
                    'path' => RemoveWatermarkService::formatterUrl($path),
                    'img' => RemoveWatermarkService::formatterUrl($img),
                    'ori_cover' => $oriCover,
                    'ori_video' => $oriVideo,
                ]
            ],
        ]);
    }
}