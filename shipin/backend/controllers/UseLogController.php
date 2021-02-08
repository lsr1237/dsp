<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/25
 * Time: 10:38
 */

namespace backend\controllers;


use backend\bases\BackendController;
use common\models\UseLogModel;
use common\models\UserModel;
use Yii;

class UseLogController extends BackendController
{
    /**
     * 获取记录列表
     * @return string
     */
    public function actionList()
    {
        $request = Yii::$app->request;
        $offset = (int)$request->get('offset', 0);
        $limit = (int)$request->get('limit', 20);
        $mobile = trim($request->get('mobile', ''));
        if (!empty($mobile)) {
            $user = UserModel::getUserByMobile($mobile);
            if (!$user) {
                return self::success(['results' => [], 'count' => 0]);
            }
        }
        $log = UseLogModel::getList($limit, $offset, $user->id ?? 0);
        foreach ($log['list'] as $row) {
            $data[] = [
                'id' => $row->id,
                'mobile' => $row->user->mobile ?? '',
                'type' => $row->type,
                'platform' => $row->platform,
                'created_at' => $row->created_at
            ];
        }
        return self::success(['results' => $data ?? [], 'count' => $log['count'] ?? 0]);
    }
}