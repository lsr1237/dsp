<?php

namespace api\controllers;

use api\bases\ApiController;
use common\models\AppInfoModel;
use Yii;
use yii\helpers\Json;

class FileController extends ApiController
{
    /**
     * 绑定访问控制过滤器
     *
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // 访问控制
        $behaviors['access']['rules'] = [
            // 允许访客用户访问的Action
            [
                'actions' => ['latest-version'],
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
     * 最新版本和下载地址
     * @return string
     */
    public function actionLatestVersion()
    {
        $request = Yii::$app->request;
        $version = trim($request->get('version', '')); // 1.0.0
        $platform = strtoupper(trim($request->get('platform', '')));
        if (empty($version)) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '当前版本号不能为空',
            ]);
        }
        if (empty($platform)) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '平台参数不能为空',
            ]);
        }
        if (!in_array($platform, [AppInfoModel::PLATFORM_ANDROID, AppInfoModel::PLATFORM_IOS])) {
            return Json::encode([
                'status' => self::STATUS_FAILURE,
                'error_message' => '平台参数不正确',
            ]);
        }
        $appInfo = AppInfoModel::findOneByPlatform($platform);
        if ($appInfo) {
            if ($version === $appInfo->latest_version) {
                return Json::encode([
                    'status' => self::STATUS_FAILURE,
                    'error_message' => '已是最新版本',
                ]);
            }
            if (
                ($appInfo->min_force_update_version !== '' && version_compare($version, $appInfo->min_force_update_version, '>='))
                &&
                ($appInfo->max_force_update_version !== '' && version_compare($version, $appInfo->max_force_update_version, '<='))
            ) {
                $state = 1; // 强制升级
            } elseif (
                ($appInfo->min_prompt_update_version !== '' && version_compare($version, $appInfo->min_prompt_update_version, '>='))
                &&
                ($appInfo->max_prompt_update_version !== '' && version_compare($version, $appInfo->max_prompt_update_version, '<='))
            ) {
                $state = 2; // 提示升级
            } else {
                $state = 0; // 用户主动升级
            }
            $data[] = [
                'latest_version' => $appInfo->latestVersion->version, // 最新版本号
                'app_url' => $appInfo->app_url, // 下载地址
                'upgrade_state' => $state, // 升级状态 0 - 用户主动升级  1 - 强制升级  2 - 提示升级
            ];
            return Json::encode([
                'status' => self::STATUS_SUCCESS,
                'error_message' => '',
                'results' => $data,
            ]);
        }
        return Json::encode([
            'status' => self::STATUS_FAILURE,
            'error_message' => '无最新版本信息',
        ]);
    }
}