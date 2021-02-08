<?php

namespace common\services;


use common\models\VideoLogModel;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\Point;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Video\X264;
use Yii;
use yii\base\Exception;
use FFMpeg\Coordinate\TimeCode;

class FFMpegService
{
    public $ffmpeg;
    public $ffprobe;

    const HEIGHT_QUALITY = '';
    const MEDIUM_QUALITY = '';
    const LOW_QUALITY = 300;

    const SPEED_ARR = [0.5, 0.75, 1.5, 1.75];

    // 创建订单
    public function __construct()
    {
        $path = Yii::$app->params['ffmpeg_path'];
        $this->ffmpeg = FFMpeg::create($path);
        $this->ffprobe = FFProbe::create($path);
    }

    /**
     * 尺寸裁剪
     * @param string $videoPath 视频路径
     * @param int $x x坐标
     * @param int $y y坐标
     * @param int $w 宽度
     * @param int $h 高度
     * @param string $savePath 视频保存路径
     * @return bool
     */
    public function sizeCutting($videoPath, $x, $y, $w, $h, $savePath)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $video
                ->filters()
                ->crop(new Point($x, $y, false), new Dimension($w, $h));
            $video->save(new X264('aac', 'libx264'), $savePath);
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 时长剪裁
     * @param string $videoPath 原视频路径
     * @param int $start 开始时间（秒）
     * @param int $len 时长（秒）
     * @param string $savePath 视频保存路径
     * @return bool
     */
    public function durationCut($videoPath, $start, $len, $savePath)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $video->filters()->clip(TimeCode::fromSeconds($start), TimeCode::fromSeconds($len));
            $video->save(new X264('aac'), $savePath);
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 添加水印（windows报错）
     * @param string $videoPath
     * @param string $watermarkPath
     * @param string $savePath 保存路径
     * @param int $x 坐标x
     * @param int $y 坐标y
     * @return bool
     */
    public function addWaterMark($videoPath, $watermarkPath, $savePath, $x = 0, $y = 0)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $video
                ->filters()
                ->watermark($watermarkPath, [
                    'position' => 'absolute',
                    'x' => $x,
                    'y' => $y,
                ]);
            $video->save(new X264('aac'), $savePath);
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 获取音频
     * @param string $videoPath
     * @param string $savePath
     * @return bool
     */
    public function getAudio($videoPath, $savePath)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $video->save(new Mp3(), $savePath);
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 视频倒放（windows目前有版本检验不通过问题）
     * @param string $videoPath
     * @param string $savePath
     * @return bool
     */
    public function reverse($videoPath, $savePath)
    {
        try {
            // 视频倒放消音
            $video = $this->ffmpeg->openAdvanced([$videoPath]);
            $video->filters()
                ->custom('[0:v]', 'reverse', '[v]');
            $video->map(['[v]'], new X264('aac'), $savePath);
            $video->save();
            // 视频、声音同时倒放
//            $video = $this->ffmpeg->openAdvanced([$videoPath]);
//            $video->setAdditionalParameters(['-vf', 'reverse', '-af', 'areverse', '-preset', 'superfast', $savePath]);
//            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 修改视频封面
     * %s -i %s -i %s -map 1 -map 0 -c copy -disposition:0 attached_pic -y %s
     * @param string $videoPath
     * @param string $imgPath
     * @param string $savePath
     * @return bool
     */
    public function modifyCover($videoPath, $imgPath, $savePath)
    {
        try {
            $video = $this->ffmpeg->openAdvanced([$videoPath, $imgPath]);
            $video->setAdditionalParameters(['-map', '1', '-map', '0', '-c', 'copy', '-disposition:0', 'attached_pic', $savePath]);
            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 去除视频音频
     * ffmpeg -i G:\hi.mp4 -c:v copy -an G:\nosound.mp4
     * @param string $videoPath
     * @param string $savePath
     * @return bool
     */
    public function delAudio($videoPath, $savePath)
    {
        try {
            $video = $this->ffmpeg->openAdvanced([$videoPath]);
            $video->setAdditionalParameters(['-c:v', 'copy', '-an', $savePath]);
            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 视频变速
     * @param $videoPath
     * @param $speed
     * @param $savePath
     * @return bool
     */
    public function speed($videoPath, $speed, $savePath)
    {
        try {
            $video = $this->ffmpeg->openAdvanced([$videoPath]);
            $parametersS = sprintf('setpts=%s*PTS', 1 / $speed);
            $parametersA = sprintf('atempo=%s', $speed);
            $video->filters()
                ->custom('[0:a]', $parametersA, '[a]')
                ->custom('[0:v]', $parametersS, '[v]');
            $video->map(['[a]', '[v]'], new X264('aac'), $savePath);
            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 视频压缩(修改视频比特率)
     * @param string $videoPath 视频初始路径
     * @param int $value 比特率值
     * @param string $savePath 视频保存路径
     * @return bool
     */
    public function compress($videoPath, $value, $savePath)
    {
        try {
            $video = $this->ffmpeg->openAdvanced([$videoPath]);
            $video->setAdditionalParameters(['-b:v', $value . 'k', $savePath]); // '-s', '540x960',
            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 修改MD5(修改视频编码方式)
     * @param string $videoPath 视频初始路径
     * @param string $savePath 视频保存路径
     * @return bool
     */
    public function md5($videoPath, $savePath)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $video->save(new X264('aac'), $savePath);
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 视频提取图片
     * @param $videoPath
     * @param $time
     * @param $savePath
     * @return bool
     */
    public function getPicture($videoPath, $time, $savePath)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $frame = $video->frame(TimeCode::fromSeconds($time));//提取第几秒的图像
            $frame->save($savePath);
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**获取视频信息
     * @param $videoPath
     * @return bool|FFProbe\DataMapping\Stream|null
     */
    public function getInfo($videoPath)
    {
        try {
            $data = $this->ffmpeg->getFFProbe()
                ->streams($videoPath)
                ->videos()
                ->first();
            return $data;
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
    }

    /**
     * 去除水印
     * ffmpeg -i logo.mp4 -filter_complex "delogo=x=100:y=100:w=100:h=100:show=1" delogo.mp4
     * @param string $videoPath
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @param string $savePath
     * @param int $show 0不显示边框 1显示边框
     * @return bool
     */
    public function removeWatermarkSingle($videoPath, $x, $y, $w, $h, $savePath, $show = 0)
    {
        try {
            $video = $this->ffmpeg->openAdvanced([$videoPath]);
            $parameters = sprintf('delogo=x=%s:y=%s:w=%s:h=%s:show=%s', $x, $y, $w, $h, $show);
            $video->filters()
                ->custom('', $parameters, '');
            $video->map([], new X264('aac'), $savePath);
            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 去除多个水印
     * @param string $videoPath
     * @param array $params
     * @param string $savePath
     * @param int $show
     * @return bool
     */
    public function removeWatermarkMulti($videoPath, $params, $savePath, $show = 0)
    {
        try {
            $video = $this->ffmpeg->openAdvanced([$videoPath]);
            $format = 'delogo=x=%s:y=%s:w=%s:h=%s:show=%s';
            $parametersArr = [];
            foreach ($params as $param) {
                $parametersArr[] = sprintf($format, $param['x'], $param['y'], $param['width'], $param['height'], $show);
            }
            $parameters = implode(',', $parametersArr);
            $video->filters()
                ->custom('', $parameters, '');
            $video->map([], new X264('aac'), $savePath);
            $video->save();
        } catch (Exception $e) {
            Yii::error($e, 'ffmpeg');
            return false;
        } catch (RuntimeException $e) {
            Yii::error(sprintf("调用ffmpeg错误信息（%s）\n %s", $e->getMessage(), $e->getPrevious()), 'ffmpeg');
            return false;
        }
        return true;
    }

    /**
     * 添加视频处理记录
     * @param $userId
     * @param $path
     * @param $savePath
     * @param $type
     * @return bool
     */
    public function addLog($userId, $path, $savePath, $type)
    {
        $data = [
            'user_id' => $userId,
            'ori_path' => $path,
            'save_path' => $savePath,
            'type' => $type,
            'state' => VideoLogModel::STATE_SUCCESS
        ];
        $ret = VideoLogModel::add($data);
        if ($ret) {
            return true;
        }
        return false;
    }

    /**
     * 获取保存路径
     * @param $path
     * @return mixed
     */
    public function getSavePath($path)
    {
        $savePath = str_replace(Yii::$app->params['video_upload_path'], Yii::$app->params['video_save_path'], $path);
        if (!is_dir(dirname($savePath))) {
            mkdir(dirname($savePath));
        }
        return $savePath;
    }
}