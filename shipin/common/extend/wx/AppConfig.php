<?php

namespace common\extend\wx;

use common\extend\wx\lib\WxPayConfigInterface;
use Yii;

/**
 * app支付配置
 * Class AppConfig
 * @package common\extend\wx
 */
class AppConfig extends WxPayConfigInterface
{
    private $appid = 'wx002bc354d8c6dd3c'; // appid
    private $merchantId = '1600481576'; // 商户号
    private $notifyUrl = ''; // 回调地址
    private $signType = 'MD5'; // 签名方式
    private $key = '9B3AF01B2F9752D7C82780EBF97B0007'; // 支付秘钥
    private $appSecret = ''; // AppSecret

    public function __construct()
    {
        $this->notifyUrl = Yii::$app->params['wx_notify'];
    }

    public function GetAppId()
    {
        return $this->appid;
    }

    public function GetMerchantId()
    {
        return $this->merchantId;
    }

    public function GetNotifyUrl()
    {
        return $this->notifyUrl;
    }

    public function GetSignType()
    {
        return $this->signType;
    }

    public function GetKey()
    {
        return $this->key;
    }
    public function GetAppSecret()
    {
        return $this->appSecret;
    }

    public function GetProxy(&$proxyHost, &$proxyPort)
    {
        $proxyHost = "0.0.0.0";
        $proxyPort = 0;
    }


    //=======【上报信息配置】===================================
    /**
     * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
     * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
     * 开启错误上报。
     * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
     * @var int
     */
    public function GetReportLevenl()
    {
        return 0;
    }
    /**
     * TODO：设置商户证书路径
     * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
     * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
     * 注意:
     * 1.证书文件不能放在web服务器虚拟目录，应放在有访问权限控制的目录中，防止被他人下载；
     * 2.建议将证书文件名改为复杂且不容易猜测的文件名；
     * 3.商户服务器要做好病毒和木马防护工作，不被非法侵入者窃取证书文件。
     * @var path
     */
    public function GetSSLCertPath(&$sslCertPath, &$sslKeyPath)
    {
        $sslCertPath = dirname(__FILE__) . '/wxpem/1600481576_cert.pem';
        $sslKeyPath = dirname(__FILE__) . '/wxpem/1600481576_key.pem';
    }
}