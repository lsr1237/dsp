<?php

namespace backend\controllers;


use backend\bases\BackendController;
use common\models\QueryConfModel;
use common\services\RedisService;
use yii\helpers\Json;
use Yii;

class QueryConfController extends BackendController
{
    /**
     * 获取app基础信息
     * @return string
     */
    public function actionIndex()
    {
        $conf = QueryConfModel::getConf();
        $data =   [
            'free_num' => $conf->free_num ?? 0,
            'email' => $conf->email ?? '',
            'wechat' => $conf->wechat ?? '',
            'official_account' => $conf->official_account ?? '',
        ];
        return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => $data, 'error_message' => '']);
    }

    /**
     * 更新app基础信息
     * @return string
     */
    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $freeNum = $request->post('free_num', 0);
        $email = trim($request->post('email', ''));
        $wechat = trim($request->post('wechat', ''));
        $officialAccount = trim($request->post('official_account', ''));
        $conf = QueryConfModel::getConf();
        $data = [
            'free_num' => $freeNum,
            'email' => $email,
            'wechat' => $wechat,
            'official_account' => $officialAccount,
        ];
        if ($conf) {
            $ret = QueryConfModel::updateByCond(['id' => $conf->id], $data);
        } else {
            $ret = QueryConfModel::add($data);
        }
        if ($ret) {
            RedisService::hMset(RedisService::KEY_QUERY_CONF, $data);
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '更新成功', 'results' => []]);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '更新失败，请稍后重试']);
    }
}