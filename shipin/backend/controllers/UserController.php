<?php


namespace backend\controllers;

use backend\bases\BackendController;
use common\extend\wx\AppletConfig;
use common\models\ErrLogModel;
use common\models\FeedbackModel;
use common\models\PayLogModel;
use common\models\UserModel;
use common\models\VideoLogModel;
use common\services\RedisService;
use common\services\UserService;
use Yii;
use yii\helpers\Json;

class UserController extends BackendController
{
    const USER_UPDATED_NUM = 1; // 修改次数
    const USER_UPDATED_VIP_END_AT = 2; // 修改会员到期时间

    /**
     * 用户管理-获取用户数据/黑名单
     * @return string
     */
    public function actionIndex()
    {
        $results = $data = [];
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $state = $request->get('state', '');
        $wxId = $request->get('wx_id', '');
        $mobile = trim($request->get('mobile', ''));
        $number = trim($request->get('number', ''));
        $num = $request->get('num', '');
        $name = trim($request->get('name', ''));
        $beginAt = $request->get('start_at', ''); // 注册起始时间
        $endAt = $request->get('end_at', ''); // 注册截止时间
        if ($num !== '') {
            $num = (int)$num;
        }
        $data = UserModel::getUserList($offset, $limit, $mobile, $name, $beginAt, $endAt, $state, $number, $num, $wxId); // 获取用户信息列表集
        foreach ($data['result'] as $row) {
            $isMember = UserService::isMember($row);
            $results[] = [
                'id' => $row->id,
                'mobile' => $row->mobile,
                'openid' => $row->openid,
                'wx_id' => $row->wx_id,
                'name' => $row->name,
                'state' => $row->state,
                'created_at' => $row->created_at,
                'member_end_at' => empty($row->end_at) ? '' : $row->end_at,
                'member_type' => $isMember ? '会员' : '非会员',
                'number' => $row->number,
                'num' => $row->num,
            ];
        }
        return self::success([
            'count' => $data['count'],
            'results' => $results
        ]);
    }

    /**
     * 用户管理-移入/移除黑名单
     * @return string
     */
    public function actionMoveToBlack()
    {
        $request = Yii::$app->request;
        $userId = $request->post('user_id');
        $user = UserModel::findUserById($userId);
        if (!$user) {
            return self::err('参数错误');
        }
        if ($user->state == UserModel::STATE_ACTIVE) {
            $saveData['state'] = UserModel::STATE_INACTIVE;
        } else {
            $saveData['state'] = UserModel::STATE_ACTIVE;
        }
        $result = UserModel::updateById($userId, $saveData);
        if ($result) {
            return self::success();
        }
        return self::err('系统错误，请稍后重试');
    }

    /**
     * 充值列表
     * @return string
     */
    public function actionRechargeLog()
    {
        $request = Yii::$app->request;
        $offset = (int)$request->get('offset', 0);
        $limit = (int)$request->get('limit', 20);
        $payWay = (int)$request->get('pay_way', 0);
        $mobile = $request->get('mobile', '');
        $number = trim($request->get('number', ''));
        $outTradeNo = $request->get('out_trade_no', '');
        $cond = [];
        if (!empty($mobile)) {
            $cond['mobile'] = $mobile;
        }
        if (!empty($number)) {
            $cond['number'] = $number;
        }
        if (!empty($cond)) {
            $user = UserModel::findOneByCond($cond);
            if (!$user) {
                return self::success(['results' => [], 'count' => 0]);
            }
        }
        $appletArr = AppletConfig::APPLET_NAME;
        $log = PayLogModel::getList($limit, $offset, $user->id ?? 0, $outTradeNo, $payWay);
        foreach ($log['list'] as $row) {
            $data[] = [
                'id' => $row->id,
                'member_name' => $row->member->name ?? '',
                'mobile' => $row->user->mobile ?? '',
                'number' => $row->user->number ?? '',
                'order' => $row->out_trade_no,
                'amount' => $row->amount,
                'pay_way' => $row->pay_way,
                'type' => $row->type,
                'state' => $row->state,
                'refund_state' => $row->refund_state,
                'wx_name' => $appletArr[$row->user->wx_id ?? 0]['name'] ?? '',
                'msg' => $row->msg,
                'end_at' => $row->end_at,
                'refund_fee' => $row->refund_fee,
                'refund_at' => $row->refund_at,
                'title' => $row->title,
                'created_at' => $row->created_at,
            ];
        }
        return self::success(['results' => $data ?? [], 'count' => (int)$log['count'] ?? 0]);
    }

    /**
     * 反馈列表
     * @return string
     */
    public function actionFeedback()
    {
        $request = Yii::$app->request;
        $mobile = trim($request->get('mobile', ''));
        $number = trim($request->get('number', ''));
        $offset = (int)$request->get('offset', 0);
        $limit = (int)$request->get('limit', 20);
        $cond = [];
        if (!empty($mobile)) {
            $cond['mobile'] = $mobile;
        }
        if (!empty($number)) {
            $cond['number'] = $number;
        }
        if (!empty($cond)) {
            $user = UserModel::findOneByCond($cond);
            if (!$user) {
                return self::success(['results' => [], 'count' => 0]);
            }
        }
        $appletArr = AppletConfig::APPLET_NAME;
        $feedback = FeedbackModel::getList($limit, $offset, $user->id ?? 0);
        foreach ($feedback['list'] as $row) {
            $data[] = [
                'mobile' => $row->user->mobile ?? '',
                'number' => $row->user->number ?? '',
                'content' => $row->content,
                'contact_info' => $row->contact_info,
                'platform' => $row->platform,
                'wx_name' => $appletArr[$row->user->wx_id ?? 0]['name'] ?? '',
                'created_at' => $row->created_at
            ];
        }
        return self::success(['results' => $data ?? [], 'count' => $feedback['count'] ?? 0]);
    }

    /**
     * 修改用户可用次数和会员到期时间
     * @return string
     */
    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $userId = (int)$request->post('id', 0);
        $type = (int)$request->post('type', 0);
        $num = (int)$request->post('num', 0); // 可用次数
        $date = trim($request->post('date', '')); // 会员到期时间
        if (!in_array($type, [self::USER_UPDATED_NUM, self::USER_UPDATED_VIP_END_AT])) { // 1 修改次数 2 修改会员到期时间
            return self::err('修改类型错误');
        }
        $user = UserModel::findUserById($userId);
        if (!$user) {
            return self::err('查无该用户');
        }
        if ($type == self::USER_UPDATED_NUM) {
            if ($num < 0) {
                return self::err('可使用次数不能为负值');
            }
            $data = ['num' => $num];
        } elseif ($type == self::USER_UPDATED_VIP_END_AT) {
            if (empty($date)) {
                return self::err('会员到期时间错误');
            }
            $data = ['end_at' => date('Y-m-d H:i:s', $date)];
        } else {
            return self::err('系统错误');
        }
        $ret = UserModel::updateByCond(['id' => $userId], $data);
        if ($ret) {
            return self::successMsg('修改成功');
        }
        return self::err('修改失败');
    }

    /**
     * 解析记录
     * @return string
     */
    public function actionAnalysisLog()
    {
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $number = trim($request->get('number', ''));
        $beginAt = $request->get('start_at', ''); // 起始时间
        $endAt = $request->get('end_at', ''); // 截止时间
        $ret = VideoLogModel::getAnalysisLog($limit, $offset, $number, $beginAt, $endAt);
        foreach ($ret['list'] as $log) {
            $data[] = [
                'id' => $log->id,
                'number' => $log->user->number ?? '',
                'input_txt' => $log->input_txt,
                'state' => $log->state,
                'created_at' => $log->created_at,
                'memo' => $log->memo,
                'ori_video' => $log->ori_video,
            ];
        }
        return self::success(['results' => $data ?? [], 'count' => (int)$ret['count'] ?? 0]);
    }

    /**
     * 错误记录
     * @return string
     */
    public function actionErrLog()
    {
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $number = trim($request->get('number', ''));
        $beginAt = $request->get('start_at', ''); // 起始时间
        $endAt = $request->get('end_at', ''); // 截止时间
        $ret = ErrLogModel::getList($limit, $offset, $number, $beginAt, $endAt, $orderBy = ['err_log.id' => SORT_DESC]);
        foreach ($ret['list'] as $log) {
            $data[] = [
                'id' => $log->id,
                'number' => $log->user->number ?? '',
                'url' => $log->url,
                'created_at' => $log->created_at,
                'msg' => $log->msg,
            ];
        }
        return self::success(['results' => $data ?? [], 'count' => (int)$ret['count'] ?? 0]);
    }

    /**
     * 报错域名统计
     * @return string
     */
    public function actionDomain()
    {
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 600);
        $errLog = ErrLogModel::getList($limit, $offset, '', '', '', ['count(err_log.id)' => SORT_DESC], true);
        foreach ($errLog as $item) {
            if (empty($item['host'])) {
                continue;
            }
            $data[$item['host']] = $item['cnt'];
        }
        if (isset($data)) {
            return self::success(['results' => $data ?? [], 'count' => (int)count($data ?? [])]);
        }
       return self::err('暂无统计数据');
    }

    /**
     * 清空域名统计
     * @return string
     */
    public function actionEmptyDomain()
    {
        $ret = ErrLogModel::update(['>', 'host', ''], ['host' => '']);
        if ($ret) {
            return self::successMsg('清空成功');
        }
        return self::err('清空失败');
    }
}