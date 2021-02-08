<?php

namespace backend\controllers;


use backend\bases\BackendController;
use common\models\HelpInfoModel;
use common\services\RedisService;
use Yii;

class HelpInfoController extends BackendController
{
    /**
     * 获取问答列表
     * @return string
     */
    public function actionList()
    {
        $request = Yii::$app->request;
        $state = (int)$request->get('state', 0);
        $wxId = $request->get('wx_id', '');
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $ret = HelpInfoModel::getList($offset, $limit, $state, $wxId);
        foreach ($ret['result'] as $row)
        {
            $data[] = [
                'id' => $row->id,
                'question' => $row->question,
                'answer' => $row->answer,
                'sort' => (string)$row->sort,
                'wx_id' => $row->wx_id,
                'created_at' => $row->created_at,
                'state' => (string)$row->state,
                'type' => $row->type
            ];
        }
        return self::success(['results' => $data ?? [], 'count' => $ret['count']]);
    }

    /**
     * 更新问答
     * @return string
     */
    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $id = (int)$request->post('id', 0);
        $wxId = (int)$request->post('wx_id', 0);
        $type = (int)$request->post('type', 0);
        $state = (int)$request->post('state', 0);
        $question = trim($request->post('question', ''));
        $answer = trim($request->post('answer', ''));
        $sort = (int)($request->post('sort', 0));
        if (!empty($id) && !empty($wxId)) {
            $info = HelpInfoModel::getOne(['id' => $id]);
            if (!$info) {
                return self::err('查无该问答，无法修改');
            }
        }
        $data = [
            'wx_id' => $wxId,
            'type' => $type,
            'state' => $state,
            'question' => $question,
            'answer' => $answer,
            'sort' => $sort,
        ];
        if (!empty($id)) {
            $ret = HelpInfoModel::update($id, $data);
        } else {
            $ret = HelpInfoModel::add($data);
        }
        if ($ret) {
            $key = sprintf('%s_%s', RedisService::KEY_HELP, $wxId);
            RedisService::delKey($key);
            return self::successMsg('修改成功');
        }
        return self::err('修改失败');
    }

    /**
     * 删除记录
     * @return string
     */
    public function actionDel()
    {
        $request = Yii::$app->request;
        $id = (int)$request->post('id', 0);
        $help = HelpInfoModel::getOne(['id' => $id]);
        if (!$help) {
            return self::err('记录不存在');
        }
        $ret = HelpInfoModel::delete($id);
        if ($ret) {
            $key = sprintf('%s_%s', RedisService::KEY_HELP, $help->wx_id);
            RedisService::delKey($key);
            return self::successMsg('操作成功');
        }
        return self::err('操作失败');
    }
}