<?php

namespace console\controllers;


use common\models\MonitorLogModel;
use common\services\WxCheckService;
use Yii;
use yii\console\Controller;
use yii\helpers\Json;

class QueryController extends Controller
{
    public function actionUpdate()
    {
        $logs = MonitorLogModel::getAllActive();
        $excIds = '';
        if ($logs) {
            $excIds = array_column($logs, 'id');
            $excIds = implode(',', $excIds);
        }
        Yii::info(sprintf('%s-名称监控更新，监控记录ID(%s)', date('Y-m-d H:i:s'), $excIds), 'query');
        foreach ($logs as $log) {
            $check = WxCheckService::checkName($log->name);
            if ($check['code'] === '' || in_array($check['code'], WxCheckService::ERROR_CODE_MAP)) {
                continue;
            }
            if ($log->code != $check['code']) {
                $ret = MonitorLogModel::updateByCond(['id' => $log->id], ['code' => (string)$check['code']]);
                if ($ret) {
                    Yii::info(sprintf('名称:(%s), ID(%s), code变化:(%s->%s)',
                        $log->name,
                        $log->id,
                        $log->code,
                        $check['code']), 'query');
                    if (isset($log->user->open_id) && !empty($log->user->open_id)) {
                        WxCheckService::sendMsg($log->user->open_id, $log->name, $check['code']);
                    }
                }
            }
        }
    }
}