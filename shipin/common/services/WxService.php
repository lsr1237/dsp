<?php

namespace common\services;


use common\extend\wx\AppConfig;
use common\extend\wx\AppletConfig;
use common\extend\wx\AppletPay;
use common\extend\wx\lib\WxPayApi;
use common\extend\wx\lib\WxPayOrderQuery;
use common\extend\wx\lib\WxPayRefund;
use common\models\PayLogModel;
use Yii;
use yii\base\Exception;

class WxService
{
    const APP_ID = 'wx002bc354d8c6dd3c'; // 应用APP_ID
    const APP_ID_APPLET = 'wx22c253a4e38f073f';
    const APP_ID_PAY_MAP = [
        self::APP_ID, // 微信支付
        'wx22c253a4e38f073f', //  短视频去水印宝
        'wxfd2c3ad00277fe65', // 短视频去水印
        'wx72791949f73ef18f' // 短视频去水印公众号
    ];
    const MCH_ID = '1600481576'; // 商户号
    const WX_KEY = '9B3AF01B2F9752D7C82780EBF97B0007'; // API密钥

    const UNIDIEDORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    const SUCCESS = 'SUCCESS'; // 成功
    const FAIL = 'FAIL'; // 失败

    // 交易状态
    const TRADE_STATE_SUCCESS = 'SUCCESS'; // SUCCESS—支付成功
    const TRADE_STATE_REFUND = 'REFUND'; // REFUND—转入退款
    const TRADE_STATE_NOTPAY = 'NOTPAY'; // NOTPAY—未支付
    const TRADE_STATE_CLOSED = 'CLOSED'; // CLOSED—已关闭
    const TRADE_STATE_REVOKED = 'REVOKED'; // REVOKED—已撤销（刷卡支付）
    const TRADE_STATE_USERPAYING = 'USERPAYING'; // REVOKED—已撤销（刷卡支付）
    const TRADE_STATE_PAYERROR = 'PAYERROR'; // PAYERROR--支付失败(其他原因，如银行返回失败)

    // 退款状态
    const REFUND_STATE_SUCCESS = 'SUCCESS'; // SUCCESS-退款成功
    const REFUND_STATE_CHANGE = 'CHANGE'; // CHANGE-退款异常
    const REFUND_STATE_REFUNDCLOSE = 'REFUNDCLOSE'; // REFUNDCLOSE—退款关闭

    /**
     * @param string $body 支付标题
     * @param string $outTradeNo 商户订单号
     * @param int $amount 交易金额单位（分）
     * @param string $ip 用户ip
     * @return bool
     */
    public static function wxPay($body, $outTradeNo, $amount, $ip)
    {
        $requestData = [
            'appid' => self::APP_ID,                         // 应用APPID
            'mch_id' => self::MCH_ID,                        // 商户号
            'trade_type' => 'APP',                           // 支付类型
            'nonce_str' => self::getNonceStr(),              // 随机字符串 不长于32位
            'body' => $body,                                 // 商品名称
            'out_trade_no' => $outTradeNo,                   // 商户后台订单号
            'total_fee' => $amount,                              // 商品价格
            'spbill_create_ip' => $ip,            // 用户端实际ip
            'notify_url' => Yii::$app->params['wx_notify'], //异步通知回调地址
        ];
        // 获取签名
        $requestData['sign'] = self::getSign($requestData);
        // 拼装数据
        $xmlData = self::setXmlData($requestData);
        $header[] = 'Content-type: text/xml';
        $res = CurlService::sendRequest(self::UNIDIEDORDER_URL, $xmlData, $header);
        if ($res) {
            $oriRes = $res;
            $res = self::xmlToArray($res);
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
                $twoData['appid'] = self::APP_ID;  // APPID
                $twoData['partnerid'] = self::MCH_ID;  //商户号
                $twoData['prepayid'] = $res['prepay_id'];  //预支付交易会话标识
                $twoData['noncestr'] = self::getNonceStr(30);
                $twoData['timestamp'] = time();   //时间戳
                $twoData['package'] = 'Sign=WXPay';   //固定值
                $twoData['sign'] = self::getTwoSign($twoData);  //二次签名
                unset($twoData['appid']);
                unset($twoData['partnerid']);
                return $twoData;
            }else{
                // 记录日志
                Yii::error(sprintf("微信获取预订单失败：\n%s", $oriRes), 'wx');
                return false;
            }

        }
        return false;
    }

    // xml数据解析函数
    public static function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    //生成xml格式的函数
    private static function setXmlData($data)
    {
        $xmlData = "<xml>";
        foreach ($data as $key => $value) {
            // $xmlData.="<".$key."><![CDATA[".$value."]]></".$key.">";
            if (is_numeric($value)){
                $xmlData.="<".$key.">".$value."</".$key.">";
            }else{
                $xmlData.="<".$key."><![CDATA[".$value."]]></".$key.">";
            }
        }
        $xmlData = $xmlData."</xml>";
        return $xmlData;
    }


    //一次签名的函数
    private static function getSign($data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            $str .= !$str ? $key . '=' . $value : '&' . $key . '=' . $value;
        }
        $str .= '&key=' . self::WX_KEY;
        $sign = strtoupper(md5($str));
        return $sign;
    }

    //二次签名的函数
    private static function getTwoSign($data)
    {
        $signData = [
            'appid' => $data['appid'],
            'partnerid' => $data['partnerid'],
            'prepayid' => $data['prepayid'],
            'noncestr' => $data['noncestr'],
            'timestamp' => $data['timestamp'],
            'package' => $data['package'],
        ];
        return self::getSign($signData);
    }

    public static function getNonceStr($length = 31)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }



    // 接收post数据
    /*
    *  微信是用$GLOBALS['HTTP_RAW_POST_DATA'];这个函数接收post数据的
    */
    public static function postData()
    {
        $receipt = $_REQUEST;
        if($receipt==null){
            $receipt = file_get_contents("php://input");
            if($receipt == null){
                $receipt = $GLOBALS['HTTP_RAW_POST_DATA'];
            }
        }
        return $receipt;
    }

    //把对象转成数组
    public static function object_toarray($arr)
    {
        if(is_object($arr)) {
            $arr = (array)$arr;
        } if(is_array($arr)) {
            foreach($arr as $key=>$value) {
                $arr[$key] = self::object_toarray($value);
            }
        }
        return $arr;
    }


    /**
     * 格式化参数格式化成url参数
     */
    private static function paramsTourl($arr, $appId)
    {
        $key = self::getKey($appId);
        $buff = "";
        foreach ($arr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff . '&key=' . $key;
    }

    public static function checkNotifySign($arr, $appId)
    {
        ksort($arr);// 对数据进行排序
        $str = self::paramsTourl($arr, $appId);//对数据拼接成字符串
        $user_sign = strtoupper(md5($str));
        if($user_sign == $arr['sign']){//验证签名
           return true;
        }else{
            return false;
        }
    }

    /**
     * 回调通知失败响应
     * @return string
     */
    public static function failTxt()
    {
        return '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 回调通知成功响应
     * @return string
     */
    public static function successTxt()
    {
        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 查询订单
     * @param string $outTradeNo
     * @param int $payWay
     * @param int $wxId
     * @return array
     */
    public static function query($outTradeNo, $payWay = PayLogModel::PAY_WAY_WECHAT, $wxId = 0)
    {
        try {
            if ($payWay == PayLogModel::PAY_WAY_WECHAT) {
                $config = new AppConfig();
            } else {
                $config = new AppletConfig($wxId);
            }
            $input = new WxPayOrderQuery();
            $input->SetOut_trade_no($outTradeNo);
            return ['state' => true, 'data' => WxPayApi::orderQuery($config, $input)];
        } catch(Exception $e) {
            return ['state' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 退款申请
     * @param $outTradeNo
     * @param $totalFee
     * @param $refundFee
     * @param int $payWay
     * @param int $wxId
     * @return array
     */
    public static function refund($outTradeNo, $totalFee, $refundFee, $payWay = PayLogModel::PAY_WAY_WECHAT, $wxId = 0)
    {
        try{
            $input = new WxPayRefund();
            $input->SetOut_trade_no($outTradeNo);
            $input->SetTotal_fee($totalFee);
            $input->SetRefund_fee($refundFee);
            $input->SetNotify_url(Yii::$app->params['wx_refund_notify']);
            if ($payWay == PayLogModel::PAY_WAY_WECHAT) {
                $config = new AppConfig();
            } else {
                $config = new AppletConfig($wxId);
            }
            $input->SetOut_refund_no('sp'.$outTradeNo);
            $input->SetOp_user_id($config->GetMerchantId());
            $ret = WxPayApi::refund($config, $input);
            return ['state' => true, 'data' => $ret];
        } catch(Exception $e) {
            return ['state' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * * 解密步骤如下：
    （1）对加密串A做base64解码，得到加密串B
    （2）对商户key做md5，得到32位小写key* ( key设置路径：微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置 )
    （3）用key*对加密串B做AES-256-ECB解密（PKCS7Padding）
     * @param string $str
     * @param string $appId
     * @return bool|mixed
     */
    public static function decryptAesData($str, $appId)
    {
        $key = self::getKey($appId);
        $xml= openssl_decrypt(base64_decode($str), 'AES-256-ECB', strtolower(md5($key)), OPENSSL_RAW_DATA, '');
        if ($xml) {
            return self::xmlToArray($xml);
        }
        return false;
    }

    /**
     * 获取KEY
     * @param $appId
     * @return string
     */
    public static function getKey($appId)
    {
        if ($appId == self::APP_ID) {
            return self::WX_KEY;
        }
        $params = array_merge(AppletConfig::APPLET_PARAMS, AppletConfig::OFFICIAL_PARAMS);
        foreach ($params as $param) {
            if ($param['appid'] == $appId) {
                return $param['key'];
            }
        }
        return '';
    }
}