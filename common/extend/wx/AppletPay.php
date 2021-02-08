<?php

namespace common\extend\wx;


use common\extend\wx\lib\WxPayApi;
use common\extend\wx\lib\WxPayException;
use common\extend\wx\lib\WxPayJsApiPay;
use common\extend\wx\lib\WxPayUnifiedOrder;
use common\extend\wx\until\WXBizDataCrypt;
use common\services\CurlService;
use common\services\RedisService;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;

class AppletPay
{
    private $config;

    public function __construct($appletType = AppletConfig::APPLET_ONE)
    {
        if (in_array($appletType, AppletConfig::OFFICIAL_ARR)) {
            $this->config = new AppletConfig($appletType, true);
        } else {
            $this->config = new AppletConfig($appletType);
        }
    }

    /**
     * 创建预订单
     * @param $body
     * @param $outTradeNo
     * @param $amount
     * @param string $openID
     * @return array
     */
    public function unifiedOrder($body, $outTradeNo, $amount, $openID)
    {
        try {
            $input = new WxPayUnifiedOrder();
            $input->SetBody($body);
            $input->SetOut_trade_no($outTradeNo);
            $input->SetTotal_fee($amount);
            $input->SetNotify_url(Yii::$app->params['wx_notify']);
            $input->SetTrade_type('JSAPI');
            $input->SetOpenid($openID);
            $order = WxPayApi::unifiedOrder($this->config, $input);
            return ['state' => true, 'data' => $this->getTwoSign($order)];
        } catch (Exception $e) {
            return ['state' => false, 'msg' => $e->getMessage()];
        }
    }

    //二次签名的函数
    public function getTwoSign($data)
    {
        $signData = [
            'appId' => $this->config->GetAppId(),
            'signType' => $this->config->GetSignType(),
            'timeStamp' => time(),
            'nonceStr' => self::getNonceStr(),
            'package' => 'prepay_id=' . $data['prepay_id'],
        ];
        $signData['paySign'] = $this->MakeSign($signData);
        unset($signData['appId']);
        unset($signData['signType']);
        return $signData;
    }

    public function MakeSign($data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->ToUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->config->GetKey();
        //签名步骤三：MD5加密或者HMAC-SHA256
        if ($this->config->GetSignType() == "MD5") {
            $string = md5($string);
        } else if ($this->config->GetSignType() == "HMAC-SHA256") {
            $string = hash_hmac("sha256", $string, $this->config->GetKey());
        } else {
            throw new WxPayException("签名类型不支持！");
        }
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams($values)
    {
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取用户OPENID
     * @param $code
     * @param bool $isOfficial
     * @return bool|false|mixed|string
     */
    public function getAuth($code, $isOfficial = false)
    {
        $appid = $this->config->GetAppId();
        $secret = $this->config->GetAppSecret();
        if ($isOfficial) {
            $result = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code");
        } else {
            $result = file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code");
        }
        $result = json_decode($result);
        if ($result && isset($result->errcode) && ($result->errcode != 0)) {
            Yii::error(sprintf('获取微信OPENID错误，%s', Json::encode($result)), 'wx');
            return false;
        }
        return $result;
    }

    /**
     * 获取union_id
     * @param $accessToken
     * @param $openid
     * @return bool|false|mixed|string
     */
    public function getUnionId($accessToken, $openid)
    {
        $result = file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openid}");
        $result = json_decode($result);
        if ($result && isset($result->errcode) && ($result->errcode != 0)) {
            Yii::error(sprintf('获取微信UNIONID错误，%s', Json::encode($result)), 'wx');
            return false;
        }
        return $result;
    }

    /**
     * 获取开放数据
     * @param $sessionKey
     * @param $encryptedData
     * @param $iv
     * @return array
     */
    public function getOpenData($sessionKey, $encryptedData, $iv)
    {
        $appid = $this->config->GetAppId();
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return ['state' => true, 'data' => json_decode($data, true)];
        } else {
            Yii::error($errCode, 'wx');
            return ['state' => false, 'error_code' => $errCode];
        }
    }


    /**
     * 获取对话凭据
     * @return bool|string
     */
    public function getAccessToken()
    {
        $appid = $this->config->GetAppId();
        $key = sprintf('%s_%s', RedisService::KEY_MCJC_ACCESS_TOKEN, $appid);
        $accessToken = RedisService::getKey($key);
        if (!$accessToken) {
            $secret = $this->config->GetAppSecret();
            $result = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}");
            $ret = json_decode($result, true);
            if (!$ret || !isset($ret['access_token'])) {
                Yii::error($result, 'wx');
                return '';
            }
            $accessToken = $ret['access_token'];
            $expiresIn = $ret['expires_in'] ?? 0;
            RedisService::setKeyWithExpire($key, $ret['access_token'], $expiresIn);
        }
        return $accessToken;
    }

    /**
     * 发送订阅消息
     * @param string $accessToken
     * @param array $data
     */
    public function subscribeMessageSend($accessToken, $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        $data = json_encode($data);
        $result = CurlService::sendRequest($url, $data);
        $ret = json_decode($result, true);
        if (!$ret || !isset($ret['errcode']) || $ret['errcode'] != 0) {
            Yii::error(sprintf('发送订阅消息：%s', $result), 'wx');
        }
    }
}