<?php

namespace backend\controllers;

use backend\bases\BackendController;
use common\extend\wx\AppletConfig;
use common\services\RedisService;
use yii\helpers\Json;
use Yii;
use common\models\NoticeModel;

class ContentController extends BackendController
{
    /**
     * 获取公告列表
     * @return string
     */
    public function actionNotice()
    {
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $state = $request->get('state', '');
        $type = $request->get('type', '');
        $wxId = $request->get('wx_id', '');
        $results = NoticeModel::getList($offset, $limit, $state, $type, $wxId);
        foreach ($results['result'] as $row) {
            $data[] = [
                'id' => $row->id,
                'title' => $row->title,
                'image' => $row->image,
                'state' => $row->state,
                'type' => $row->type,
                'content' => $row->content,
                'updated_at' => $row->updated_at,
                'sort' => $row->sort,
                'wx_id' => $row->wx_id,
                'app_id' => $row->app_id,
                'is_jump' => $row->is_jump,
                'url' => $row->url,
                'wx_name' => AppletConfig::APPLET_NAME[$row->wx_id]['name'] ??''
            ];
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'count' => $results['count'],
            'results' => $data ?? []
        ]);
    }

    /**
     * 添加公告
     * @return string
     */
    public function actionNoticeAdd()
    {
        $request = Yii::$app->request;
        $title = trim($request->post('title', ''));
        if (empty($title)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入标题']);
        }
        $image = trim($request->post('image', ''));
        $url = trim($request->post('url', ''));
        $state = (int)$request->post('state', 0);
        if (empty($state)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选择状态']);
        }
        if (!in_array($state, [NoticeModel::STATE_SHOW, NoticeModel::STATE_HIDE])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '公告显示状态非法']);
        }
        $sort = (int)$request->post('sort', 1);
        $content = trim($request->post('content', ''));
//        if (empty($content)) {
//            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '内容不能为空']);
//        }
        $type = (int)$request->post('type', 0);
        if (empty($type)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选择类型']);
        }
        $isJump = (int)$request->post('type', 0); // 是否跳转
        if (!in_array($isJump, [NoticeModel::JUMP, NoticeModel::NO_JUMP])) {
            return self::err('是否跳转小程序类型错误');
        }
        $appId = trim($request->post('app_id', '')); // 跳转appid
        $wxId = (int)$request->post('wx_id', 1); // 所属小程序编号
        $data = [
            'title' => $title,
            'image' => $image,
            'state' => $state,
            'content' => $content,
            'sort' => $sort,
            'type' => $type,
            'url' => $url,
            'wx_id' => $wxId,
            'app_id' => $appId,
            'is_jump' => $isJump
        ];
        $ret = NoticeModel::add($data);
        if (!$ret) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '保存失败']);
        }
        $key = sprintf('%s_%s', RedisService::KEY_BANNER, $wxId);
        RedisService::delKey($key);
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '保存成功']);
    }

    /**
     * 根据公告id获取公告内容
     * @return string
     */
    public function actionGetNotice()
    {
        $request = Yii::$app->request;
        $id = $request->get('id', 0);
        $result = NoticeModel::getOneById($id);
        if (!$result) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '查无该公告']);
        }
        $data = [
            'id' => $result->id,
            'title' => $result->title,
            'state' => (string)$result->state,
            'content' => $result->content,
            'image' => $result->image,
            'sort' => $result->sort,
            'type' => (string)$result->type,
            'url' => $result->url,
            'wx_id' => $result->wx_id,
            'app_id' => $result->app_id,
            'is_jump' => (string)$result->is_jump,
        ];
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'results' => $data
        ]);
    }

    /**
     * 更新公告内容
     * @return string
     */
    public function actionNoticeUpdate()
    {
        $request = Yii::$app->request;
        $id = (int)$request->get('id');
        if (empty($id)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数有误']);
        }
        $model = NoticeModel::getOneById($id);
        if (!$model) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '公告不存在']);
        }
        $title = trim($request->post('title', ''));
        if (empty($title)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入公告标题']);
        }
        $image = trim($request->post('image', ''));
        $url = trim($request->post('url', ''));
        $state = intval($request->post('state', 0));
        if (empty($state)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请上选择公告的状态']);
        }
        if (!in_array($state, [NoticeModel::STATE_SHOW, NoticeModel::STATE_HIDE])) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '公告显示状态非法']);
        }
        $sort = (int)$request->post('sort', 1);
        $content = trim($request->post('content', ''));
//        if (empty($content)) {
//            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '公告的内容不能为空']);
//        }
        $type = (int)$request->post('type', 0);
        if (empty($type)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选择类型']);
        }
        $isJump = (int)$request->post('type', 0); // 是否跳转
        if (!in_array($isJump, [NoticeModel::JUMP, NoticeModel::NO_JUMP])) {
            return self::err('是否跳转小程序类型错误');
        }
        $appId = trim($request->post('app_id', '')); // 跳转appid
        $wxId = (int)$request->post('wx_id', 1); // 所属小程序编号
        $data = [
            'title' => $title,
            'image' => $image,
            'state' => $state,
            'content' => $content,
            'sort' => $sort,
            'type' => $type,
            'url' => $url,
            'wx_id' => $wxId,
            'app_id' => $appId,
            'is_jump' => $isJump
        ];
        $ret = NoticeModel::update($model->id, $data);
        if (!$ret) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '保存失败']);
        }
        $key = sprintf('%s_%s', RedisService::KEY_BANNER, $wxId);
        RedisService::delKey($key);
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '保存成功']);
    }

    /**
     * 删除公告
     * @return string
     */
    public function actionNoticeDel()
    {
        $request = Yii::$app->request;
        $id = (int)$request->post('notice_id', '');
        $result = NoticeModel::delNoticeById($id);
        if (!$result) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '删除失败']);
        }
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '删除成功']);
    }
}