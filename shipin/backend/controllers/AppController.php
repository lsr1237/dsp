<?php

namespace backend\controllers;

use common\extend\wx\AppletConfig;
use common\models\AppBasicModel;
use common\models\AppInfoModel;
use common\models\AppIntrosModel;
use common\services\RedisService;
use dosamigos\qrcode\QrCode;
use yii\helpers\Json;
use Yii;
use backend\bases\BackendController;

class AppController extends BackendController
{
    // 展示当前版本信息
    public function actionIndex()
    {
        $android = AppInfoModel::findOneByPlatform(AppInfoModel::PLATFORM_ANDROID);
        $ios = AppInfoModel::findOneByPlatform(AppInfoModel::PLATFORM_IOS);
        $androidData = [
            'version' => $android->latest_version ?? '',
            'app_url' => $android->app_url ?? '',
            'link_url' => $android->link_url ?? '',
            'platform' => $android->platform ?? '',
            'min_force_update_version' => $android->min_force_update_version ?? '',
            'max_force_update_version' => $android->max_force_update_version ?? '',
            'min_prompt_update_version' => $android->min_prompt_update_version ?? '',
            'max_prompt_update_version' => $android->max_prompt_update_version ?? '',
            'intro' => $android->latestVersion->intro ?? '',
            'restricted_platform' => $android->latestVersion->restricted_platform ?? ''
        ];
        $iosData = [
            'version' => $ios->latest_version ?? '',
            'app_url' => $ios->app_url ?? '',
            'link_url' => $ios->link_url ?? '',
            'platform' => $ios->platform ?? '',
            'min_force_update_version' => $ios->min_force_update_version ?? '',
            'max_force_update_version' => $ios->max_force_update_version ?? '',
            'min_prompt_update_version' => $ios->min_prompt_update_version ?? '',
            'max_prompt_update_version' => $ios->max_prompt_update_version ?? '',
            'intro' => $ios->latestVersion->intro ?? '',
            'restricted_platform' => $ios->latestVersion->restricted_platform ?? ''
        ];
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'results' => [
                'android' => $androidData ?? [],
                'ios' => $iosData ?? [],
            ],
        ]);
    }

    // 编辑app信息
    public function actionEdit()
    {
        $request = Yii::$app->request;
        $version = trim($request->post('version', ''));
        $appUrl = trim($request->post('app_url', ''));
        $plistUrl = trim($request->post('plist_url', ''));
        $platform = trim($request->post('platform', ''));
        $minForceUpdateVersion = trim($request->post('min_force_update_version', ''));
        $maxForceUpdateVersion = trim($request->post('max_force_update_version', ''));
        $minPromptUpdateVersion = trim($request->post('min_prompt_update_version', ''));
        $maxPromptUpdateVersion = trim($request->post('max_prompt_update_version', ''));
        $intro = trim($request->post('intro', ''));
        $restrictedPlatform = trim($request->post('restricted_platform', ''));
        // 校验字段
//        if ($plistUrl == 'undefined' && $platform == 'IOS') {
//            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => 'plist文件不能为空']);
//        }
        if (!$version) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '版本名称不能为空']);
        }
        if ((mb_strlen($version, 'utf-8')) > 12) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '版本名称长度填写不能超过12个字符']);
        }
        if (!$appUrl) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => 'apk/ipa下载地址不能为空，请填写正确的的下载地址']);
        }
        if (!$platform) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '所操作的平台参数错误']);
        }
        if (!in_array(strtoupper($platform), [AppInfoModel::PLATFORM_IOS, AppInfoModel::PLATFORM_ANDROID])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '平台参数错误']);
        }
        if (!$intro) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '版本更新说明不能为空']);
        }
        $appIntrosData = [
            'version' => $version,
            'intro' => $intro,
            'restricted_platform' => $restrictedPlatform,
        ];
        $appInfoData = [
            'latest_version' => $version,
            'app_url' => $appUrl,
            'link_url' => $appUrl,
            'platform' => strtoupper($platform),
            'min_force_update_version' => $minForceUpdateVersion,
            'max_force_update_version' => $maxForceUpdateVersion,
            'min_prompt_update_version' => $minPromptUpdateVersion,
            'max_prompt_update_version' => $maxPromptUpdateVersion,
        ];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!AppIntrosModel::update($appIntrosData)) {
                throw new \Exception('app更新说明保存失败！');
            }
            if (!AppInfoModel::update($appInfoData)) {
                throw new \Exception('app信息保存失败！');
            }
            // 保存二维码图片
            $message = '';
            /* if (!is_dir(Yii::$app->params['app_qrcode_path'])) {
                $message = 'apk上传成功,二维码保存目录不存在，请先创建该目录,再执行保存操作！';
            }
            $fileName = sprintf('%s%s.png', Yii::$app->params['app_qrcode_path'], strtolower($platform));
            $url = $linkUrl ?? $appUrl;
            QrCode::png($url , $fileName, 0, 4); */
            $transaction->commit();
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => $message]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }

    }

    // 获取所有的app更新说明及版本名称
    public function actionAppInfo()
    {
        $appData = $data = $result = [];
        $platform = trim(Yii::$app->request->get('platform', ''));
        $appInfo = AppInfoModel::findOneByPlatform(strtoupper($platform));
        $appData = [
            'version' => $appInfo->latest_version ?? '',
            'app_url' => $appInfo->app_url ?? '',
            'link_url' => $appInfo->link_url ?? '',
            'platform' => $appInfo->platform ?? $platform,
            'min_force_update_version' => $appInfo->min_force_update_version ?? '',
            'max_force_update_version' => $appInfo->max_force_update_version ?? '',
            'min_prompt_update_version' => $appInfo->min_prompt_update_version ?? '',
            'max_prompt_update_version' => $appInfo->max_prompt_update_version ?? '',
            'intro' => $appInfo->latestVersion->intro ?? '',
            'restricted_platform' => $appInfo->latestVersion->restricted_platform ?? '',
        ];
        $result = AppIntrosModel::getAllVersion();
        foreach ($result as $row) {
            $data[] = [
                'version' => $row->version,
            ];
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'results' => [
                'appData' => $appData ?? [],
                'version' => $data ?? [],
            ],
        ]);
    }

    // 获取所有的app版本信息
    public function actionGetVersion()
    {
        $result = $data = [];
        $version = Yii::$app->request->get('name', '');
        $result = AppIntrosModel::findOneByVersion($version);
        $data = [
            'version' => $result->version ?? '',
            'intro' => $result->intro ?? '',
            'restricted_platform' => $result->restricted_platform ?? '',
        ];
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'results' => $data,
        ]);
    }

    /**
     * 获取app基础信息
     * @return string
     */
    public function actionBasic()
    {
        $request = Yii::$app->request;
        $appletId = (int)$request->get('applet_id', 0); // 小程序id
        $appBasic = AppBasicModel::getAppBasic($appletId);
        if ($appBasic) {
            if (!empty($appBasic->config)) {
                $data = json_decode($appBasic->config, true);
            }
            if (!empty($appBasic->id_config)) {
                $idConfig = json_decode($appBasic->id_config, true);
            }
        }
        foreach (AppletConfig::APPLET_NAME as $key => $row) {
            $appletArr[] = [
                'key' => $key,
                'name' => $row['name']
            ];
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => $data ?? null, 'id_config' => $idConfig ?? null, 'applet_arr' => $appletArr ?? []]);
    }

    /**
     * 更新app基础信息
     * @return string
     */
    public function actionUpdateBasic()
    {
        $request = Yii::$app->request;
        $appletId = (int)$request->post('applet_id', 0); // 小程序id
        $appBasic = AppBasicModel::getAppBasic($appletId);
        $idConfig = trim($request->post('id_config', ''));
        if (empty($idConfig)) {
            $wechat = trim($request->post('wechat', '')); // 客服电话
            $openVipTxt = trim($request->post('open_vip_txt', '')); // 开通Vip文案
            $shareTitle = trim($request->post('share_title', '')); // 首页分享标题
            $tip = trim($request->post('tip', '')); // 首页输入框上方文案
            $button = trim($request->post('button', '')); // 公众号按钮
            $link = trim($request->post('link', '')); // 公众号链接
            $subscription = trim($request->post('subscription', '')); // 公众号
            $freeNum = (int)$request->post('free_num', ''); // 每日免费次数
            $rewardNum = (int)$request->post('reward_num', ''); // 邀请奖励次数
            $limitNum = (int)$request->post('limit_num', ''); // 每日广告限制次数
            $auditState = (int)$request->post('audit_state', ''); // 审核状态
            $data = [
                'wechat' => $wechat,
                'open_vip_txt' => $openVipTxt,
                'share_title' => $shareTitle,
                'tip' => $tip,
                'button' => $button,
                'link' => $link,
                'free_num' => $freeNum,
                'reward_num' => $rewardNum,
                'limit_num' => $limitNum,
                'subscription' => $subscription,
                'audit_state' => $auditState
            ];
            $saveData = [
                'wx_id' => $appletId,
                'config' => json_encode($data),
            ];
        } else {
            $saveData = [
                'wx_id' => $appletId,
                'id_config' => $idConfig,
            ];
        }
        if ($appBasic) {
            $ret = AppBasicModel::update($appBasic->id, $saveData);
        } else {
            $ret = AppBasicModel::add($saveData);
        }
        if ($ret) {
            if (isset($data)) {
                $key = sprintf('%s_%s', RedisService::KEY_APP_BASIC, $appletId);
                RedisService::hMset($key, $data);
            }
            if (!empty($idConfig)) {
                $key = sprintf('%s_%s', RedisService::KEY_AD_IDS, $appletId);
                RedisService::setKey($key, $idConfig);
            }
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '更新成功', 'results' => []]);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '更新失败，请稍后重试']);
    }
}