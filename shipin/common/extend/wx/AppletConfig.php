<?php

namespace common\extend\wx;

use common\extend\wx\lib\WxPayConfigInterface;
use Yii;

/**
 * 小程序支付配置
 * Class AppletConfig
 * @package common\extend\wx
 */
class AppletConfig extends WxPayConfigInterface
{
    const APPLET_ONE = 1; // 小程序1
    const APPLET_TWO = 2; // 小程序2
    const APPLET_THREE = 3; // 小程序3
    const APPLET_FOUR = 4; // 名称检测小程序
    const APPLET_FIVE = 5; // 小程序5
    const APPLET_SIX = 6; // 小程序6
    const OFFICIAL_ONE = 11; // 公众号1
    const APPLET_ARR = [
        self::APPLET_ONE,
        self::APPLET_TWO,
        self::APPLET_THREE,
        self::APPLET_FIVE,
        self::APPLET_SIX,
    ];
    const APPLET_NAME = [
        0 => [
            'name' => 'APP版本'
        ],
        self::APPLET_ONE => [
            'name' => '短视频去水印宝',
        ],
        self::APPLET_TWO => [
            'name' => '短视频水印王',
        ],
        self::APPLET_THREE => [
            'name' => '去水印短视频编辑工具',
        ],
        self::APPLET_FIVE => [
            'name' => '短视频去水印',
        ],
        self::APPLET_SIX => [
            'name' => '视频剪辑王',
        ],
    ];

    const OFFICIAL_ARR = [
        self::OFFICIAL_ONE
    ];
    const OFFICIAL_PARAMS = [
        self::OFFICIAL_ONE => [
            'appid' => 'wx72791949f73ef18f', // 短视频去水印 公众号
            'appSecret' => '1a3cd8bdda4e9aa31cb15eb28e00a7da',
            'merchantId' => '1509514791', // 商户号
            'key' => 'hy4cenyh3un3jsnn0ecby5xs40vboe5a', // 支付秘钥
        ],
    ];
    const OFFICIAL_APPLET = [
        self::APPLET_FIVE => self::OFFICIAL_ONE
    ];
    const APPLET_PARAMS = [
        self::APPLET_ONE => [
            'appid' => 'wx22c253a4e38f073f', // 短视频去水印宝 1或空
            'appSecret' => '63a973c3add9366f8db954761d99f6b3',
            'merchantId' => '1600481576',
            'key' => '9B3AF01B2F9752D7C82780EBF97B0007',
        ],
        self::APPLET_TWO => [
            'appid' => 'wx89ea309f71f9badd', // 短视频水印王 2
            'appSecret' => '17ed3d939e7efc121ebb6b73e526af06',
            'merchantId' => '',
            'key' => '',
        ],
        self::APPLET_THREE => [
            'appid' => 'wxb83fec5a92c1075e', // 去水印短视频编辑工具 3
            'appSecret' => '629643305490a04184eb85fa2bb94fd0',
            'merchantId' => '',
            'key' => '',
        ],
        self::APPLET_FIVE => [
            'appid' => 'wxfd2c3ad00277fe65', // 短视频去水印
            'appSecret' => 'ade754afdc86120b4491b748143db8f8',
            'merchantId' => '1509514791',
            'key' => 'hy4cenyh3un3jsnn0ecby5xs40vboe5a',
        ],
        self::APPLET_SIX => [
            'appid' => 'wxe3c012f55916912d', // 短视频去水印
            'appSecret' => '2a317a3078ffebd7ec532463ca798db1',
            'merchantId' => '1605440990',
            'key' => 'hy4cenyh3un3jsnn0ecby5xs40vboe5b',
        ],
        self::APPLET_FOUR => [
            'appid' => 'wx868b5a75fe39d3ab', // 名称检测小程序
            'appSecret' => 'f23cb5d95f1d830107783e065eb5070f',
        ],
    ];

    private $appid = ''; // 'wx22c253a4e38f073f'; // appid
    private $merchantId = ''; // 商户号
    private $notifyUrl = ''; // 回调地址
    private $signType = 'MD5'; // 签名方式
    private $key = ''; // 支付秘钥
    private $appSecret = ''; // '63a973c3add9366f8db954761d99f6b3'; // AppSecret

    public function __construct($appletType = self::APPLET_ONE, $isOfficial = false)
    {
        $this->notifyUrl = Yii::$app->params['wx_notify'];
        if ($isOfficial) {
            $params = self::OFFICIAL_PARAMS[$appletType] ?? '';
        } else {
            $params = self::APPLET_PARAMS[$appletType] ?? '';
        }
        if ($params) {
            $this->appid = $params['appid'];
            $this->appSecret = $params['appSecret'];
            $this->merchantId = $params['merchantId'] ?? '';
            $this->key = $params['key'] ?? '';
        }
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
        $sslCertPath = dirname(__FILE__) . "/wxpem/{$this->merchantId}_cert.pem";
        $sslKeyPath = dirname(__FILE__) . "/wxpem/{$this->merchantId}_key.pem";
    }
}