<?php

namespace backend\controllers;

use backend\bases\BackendController;
use backend\services\FileService;
use common\extend\wx\AppletConfig;
use common\models\MemberCardModel;
use common\services\RedisService;
use yii\helpers\Json;
use Yii;

class SysController extends BackendController
{
    const ACT_TYPE_ADD = 'add'; // 添加操作
    const ACT_TYPE_UPDATE = 'update'; // 添加操作

    /**
     * 会员卡列表
     * @return string
     */
    public function actionMemberCard()
    {
        $request = Yii::$app->request;
        $type = (int)$request->get('type', 0);
        $appletId = (int)$request->get('applet_id', 0); // 小程序id
        $cond = [];
        if ($type) {
            $cond['type'] = $type;
        }
        if ($appletId !== '') {
            $cond['wx_id'] = $appletId;
        }
        $appletArr = AppletConfig::APPLET_NAME;
        $ret = MemberCardModel::getAllByCond($cond);
        foreach ($ret as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
                'ori_price' => $row->ori_price,
                'cur_price' => $row->cur_price,
                'term' => $row->term,
                'sort' => $row->sort,
                'state' => $row->state,
                'type' => $row->type,
                'num' => $row->num,
                'wx_id' => $row->wx_id,
                'wx_name' => $appletArr[$row->wx_id]['name'] ?? '',
                'created_at' => $row->created_at,
            ];
        }
        foreach ($appletArr as $key => $row) {
            $applet[] = [
                'key' => $key,
                'name' => $row['name']
            ];
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'count' => (int)count($data ?? []),
            'results' => $data ?? [],
            'applet_arr' => $applet
        ]);
    }

    /**
     * 保存会员卡数据
     * @return string
     */
    public function actionMemberCardSave()
    {
        $request = Yii::$app->request;
        $name = trim($request->post('name', ''));
        $wxId = (int)$request->post('wx_id', 0);
        $oriPrice = $request->post('ori_price', 0);
        $curPrice = $request->post('cur_price', 0);
        $sort = (int)$request->post('sort', 0);
        $type = (int)$request->post('type', 0);
        $state = (int)$request->post('state', 0);
        $term = (int)$request->post('term', 0);
        $num = (int)$request->post('num', 0);
        $act = $request->post('act', '');
        $appletArr = AppletConfig::APPLET_ARR;
        if (!in_array($wxId, $appletArr)) {
            return self::err('所属小程序不正确');
        }
        if (!in_array($act, [self::ACT_TYPE_ADD, self::ACT_TYPE_UPDATE])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '操作类型错误']);
        }
        if (!in_array($type, [MemberCardModel::TYPE_VIP, MemberCardModel::TYPE_NUM])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '类型错误']);
        }
        if ($type == MemberCardModel::TYPE_VIP) {
            $num = 0;
        } elseif ($type == MemberCardModel::TYPE_NUM) {
            $term = 0;
        }
        if (!$name) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '名称不能为空']);
        }
        if (!in_array($state, [MemberCardModel::STATE_ACTIVE, MemberCardModel::STATE_INACTIVE])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '状态参数错误']);
        }
        $data = [
            'name' => $name,
            'ori_price' => $oriPrice,
            'cur_price' => $curPrice,
            'term' => $term,
            'sort' => $sort,
            'state' => $state,
            'type' => $type,
            'num' => $num,
            'wx_id' => $wxId,
        ];
        $key = sprintf('%s_%s', RedisService::KEY_MEMBER_CARDS, $wxId);
        if ($act == self::ACT_TYPE_ADD) {
            $ret = MemberCardModel::add($data);
            if ($ret) {
                RedisService::delKey($key);
                return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '操作成功']);
            }
        } elseif ($act == self::ACT_TYPE_UPDATE) {
            $id = (int)$request->post('id', 0);
            if (!$id) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => 'ID参数错误']);
            }
            $ret = MemberCardModel::updateByCond(['id' => $id], $data);
            if ($ret) {
                RedisService::delKey($key);
                return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '操作成功']);
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '保存失败']);
    }

    /**
     * 获取视频配置
     * @return string
     */
    public function actionVideoConf()
    {
        $data =   [
            'del_file_day' =>  $delFileDay = Yii::$app->params['del_file_day'] ?? 0,
        ];
        return Json::encode(['status' => self::STATUS_SUCCESS, 'results' => $data, 'error_message' => '']);
    }

    public function actionVideoConfUpdate()
    {
        $request = Yii::$app->request;
        $delDay = (int)$request->post('del_file_day', 0);
        if ($delDay < 0) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '删除天数应该大于0']);
        }
        $path = dirname(dirname(__FILE__));
        $file = $path . '/../common/config/video.php';
        $data = [
            'del_file_day' => $delDay,
        ];
        if (FileService::saveParams($data, $file, true)) {
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '保存失败']);
    }
}