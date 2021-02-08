<?php
namespace common\extend\im;

use common\models\DingTalkToken;
use yii\helpers\Json;
use Yii;

class DingTalkIm {
    const CORP_ID = 'ding47d1d5e8bc31912935c2f4657eb6378f';
    const CORP_SECRET = 'v5mH0rfFRhS1xW4zgDwttYG2aY3lSZRhTJnoLGwoDgU37s4SV4r2zUOe3CJA-Sbe';

    const GET_TOKEN_URL = 'https://oapi.dingtalk.com/gettoken?corpid=%s&corpsecret=%s';
    const CHAT_SEND_URL = 'https://oapi.dingtalk.com/chat/send?access_token=%s';

    // 发消息人，必须在发放对象的群里，目前用'旺财'
    const SENDER_WANGCAI = 'e21dedb189b2012453c6e9e6bbd10ea5543c4a724bfe5523b025191d70d8c6b9';
    // 发消息人，必须在发放对象的群里，目前用'小白'
    const SENDER_XIAOBAI = '6ca909a069523996f52b428a5b102c5e3be459ebf0a9e5f21eddc6d1c31b7c6e';

    // 群聊 chat id
    // 总群
    const COMPANY_CHAT_ID = 'chat691cf1f3d76099be5fb10a3294be5264';
    // 开发部门
    const TECH_DEPT_CHAT_ID = 'chat9bc804195187b967a2992c5a3fc6415c';
    // 客服部门
    const CUSTOMER_SERVICE_CHAT_ID = 'chat9bc804195187b967a2992c5a3fc6415c';
    // 产品+开发群
    const PRODUCT_TECH_CHAT_ID = 'chat9bc804195187b967a2992c5a3fc6415c';

    const ROBOT_WEBHOOK = 'https://oapi.dingtalk.com/robot/send?access_token=';

    public $enable;

    public $notice_configs;

    // 根据配置发送机器人提醒
    public function sendNotice($noticeType, $chatId, $text)
    {
        if(!$this->notice_configs || !$this->notice_configs[$noticeType]) {
            Yii::warning("No configurations for " . $noticeType);
            return false;
        }
        $config = $this->notice_configs[$noticeType];
        $atMobiles = $config['atMobiles'];
        // 钉钉目前最多可以@5个人，大于5个随机发
        if (count($atMobiles) > 5) {
            shuffle($atMobiles);
        }
        return $this->sendTextToChatGroup($config['sender'], $chatId, $text, $atMobiles, $config['isAtAll']);
    }

    public function sendTextToChatGroup($sender, $chatId, $text, $atMobiles = [], $isAtAll = false)
    {
        if (!$this->enable) {
            Yii::warning("Dingtalk not enabled: " . $text, 'ding_talk_im');
            return false;
        }
        $msg = [
            'msgtype' => 'text',
            'chatid' => $chatId,
            'text' => ['content' => $text],
            'at' => [
                'atMobiles' => $atMobiles,
                "isAtAll" => $isAtAll,
            ],
        ];
        $result = $this->sendPost(self::ROBOT_WEBHOOK . $sender, $msg);
        return $this->verifyResult($result, $msg);
    }

    private function verifyResult($result, $msg)
    {
        if ($result === false) {
            Yii::error("Failed to send to Dingtalk API: " . var_export($msg, true), 'ding_talk_im');
            return false;
        }
        $obj = Json::decode($result);
        if (!isset($obj['errcode']) || $obj['errcode'] != 0) {
            Yii::error("Dingtalk chat/send failed: "
                . var_export($msg, true)
                . $result, 'ding_talk_im');
            return false;
        }
        return true;
    }

    private function sendPost($url, $data)
    {
        Yii::info('发送消息, URL:' . $url . ', data:' . Json::encode($data), 'ding_talk_im');
        $ch = curl_init($url);
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
