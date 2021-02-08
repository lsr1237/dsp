<?php

namespace api\controllers;


use api\bases\ApiController;
use common\extend\utils\IPUtils;
use common\extend\video\src\VideoManager;
use common\services\FFMpegService;
use common\services\RedisService;
use common\services\RemoveWatermarkService;
use common\services\WxCheckService;
use common\services\WxService;
use Yii;

class HappyController extends ApiController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => ['test', 'wx-test', 'test-watermark'],
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

    public function actionTest()
    {
        $request = Yii::$app->request;
        $key = $request->get('key', '');
        $mobile = $request->get('mobile', '');
        if ($mobile == '13950062795') {
            var_dump(RedisService::getKey($key));
        } else {
            var_dump('失败');
        }
    }


    public function actionWxTest()
    {
        $request = Yii::$app->request;
        $name = $request->get('name', '');
        $mobile = $request->get('mobile', '');
        if ($mobile == '13950062795') {
            var_dump(WxCheckService::checkName($name));
        } else {
            var_dump('失败');
        }
    }

    public function actionTestWatermark()
    {
        $request = Yii::$app->request;
        $link = $request->get('link', '');
        $mobile = $request->get('mobile', '');
        if ($mobile == '13950062795') {
            preg_match_all('/https?:\/\/[\w-~.:%#=&?\/\\\]+/i', $link, $urlArr);
            $url = $urlArr[0][0] ?? '';
            $return = VideoManager::DouYin()->start($url);
            if (!isset($return['video_url']) || empty($return['video_url'])) {
                var_dump($return, IPUtils::getUserIP());
                $return = RemoveWatermarkService::getDyRawUrl($url);
            }
            var_dump($url, $return);
        } else {
            var_dump('失败');
        }
    }
}