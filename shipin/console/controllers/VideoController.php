<?php

namespace console\controllers;


use yii\base\Exception;
use yii\console\Controller;
use Yii;

class VideoController extends Controller
{
    /**
     * 删除超期文件
     */
    public function actionDelFile()
    {
        Yii::info(sprintf('执行删除过期文件任务：%s', date('Y-m-d H:i:s')), 'video');
        $delFileDay = Yii::$app->params['del_file_day'] ?? 0;
        if ($delFileDay <= 0) {
            Yii::error(sprintf('删除N天钱的文件天数小于等于0，当前值为：%s', $delFileDay), 'video');
            return;
        }
        // 获取时间
        $delDate = date('Ymd', strtotime("-{$delFileDay}days"));
        $delDateInt = strtotime($delDate);
        $delFileDirList = [
            Yii::$app->params['video_upload_path'] ?? '',
            Yii::$app->params['video_save_path'] ?? '',
            Yii::$app->params['img_upload_path'] ?? '',
            Yii::$app->params['img_save_path'] ?? '',
        ];
        try {
            foreach ($delFileDirList as $path) {
                if ($path && $path != '/') {
                    $fileList = self::getDirList($path);
                    foreach ($fileList as $file) {
                        $timeInt = strtotime($file['name']);
                        if ($timeInt && $timeInt <= $delDateInt) {
                            Yii::info(sprintf('删除文件夹：%s', $file['dir']), 'video');
                            if (is_dir($file['dir']) && $file != '/') {
                                self::delDir($file['dir']);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Yii::error($e, 'video');
        }
    }

    /**
     * 获取文件夹列表
     * @param $dir
     * @return array
     */
    private static function getDirList($dir)
    {
        $dirList = [];
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullPath = $dir . "/" . $file;
                if (!is_dir($fullPath)) {

                } else {
                    if (strtotime($file)) {
                        array_push($dirList, ['dir' => $fullPath, 'name' => $file]);
                    }
                }
            }
        }
        closedir($dh);
        return $dirList;
    }

    /**
     * 删除文件夹
     * @param $dir
     * @return bool
     */
    private static function delDir($dir)
    {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullPath = $dir . "/" . $file;
                if (!is_dir($fullPath)) {
                    unlink($fullPath);
                } else {
                    self::delDir($fullPath);
                }
            }
        }
        closedir($dh);
        if (rmdir($dir)) { // 删除当前空文件夹：
            return true;
        } else {
            return false;
        }
    }
}