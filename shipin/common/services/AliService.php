<?php

namespace common\services;


use Yii;
use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;

class AliService
{
    const STATE_TRADE_FINISHED = 'TRADE_FINISHED'; // 交易完成
    const STATE_TRADE_SUCCESS = 'TRADE_SUCCESS'; // 交易成功
    const STATE_WAIT_BUYER_PAY = 'WAIT_BUYER_PAY'; // 交易创建
    const STATE_TRADE_CLOSED = 'TRADE_CLOSED'; // 未付款交易超时关闭，或支付完成后全额退款
    const API_ERROR_CODE = '40004'; // 业务处理失败
    const API_SUCCESS_CODE = '10000'; // 接口调用成功
    const TRADE_NOT_EXIST = 'ACQ.TRADE_NOT_EXIST'; // 订单不存在
    const FUND_CHANGE_Y = 'Y'; // 退款发生资金变化
    const FUND_CHANGE_N = 'N'; // 退款没有发生资金变化

//    public $sellerId = '2088102176410073'; // 商户UID 沙箱
//    public $appId = '2016092000551677'; // 应用APP_ID 沙箱

    public $sellerId = '2088211144070053'; // 商户UID
    public $appId = '2021001190633143'; // 应用APP_ID

    // 创建订单
    public function __construct()
    {
        Factory::setOptions($this->getOptions());
    }

    private function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        // $options->gatewayHost = 'openapi.alipaydev.com'; // 沙箱环境网关地址
        $options->signType = 'RSA2';

        $options->appId = $this->appId; // <-- 请填写您的AppId，例如：2019022663440152 -->

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        // $options->merchantPrivateKey = '<-- 请填写您的应用私钥，例如：MIIEvQIBADANB ... ... -->';
        // 沙箱
        // $options->merchantPrivateKey = 'MIIEpAIBAAKCAQEAo2dsgDXfKRfeXdckcObZ7m2RErb3xV7nn7nosUVbWH1YpByZZVuYBGENpWGwZ1K4x3KJ0qsxYK3ppJ2ISFiFW3W8KnEcYiDOiTKf+zlDLF+RcMv9ePJxD+1W6YAGOfQNiIBvmDyzKl1SNKYNUMocLEPN6o2Yiz6jof28E5Jvs/08eLItfdCvIFcMpwVjQ47rjYOf+Dql+lgEa3sWzCoPNiXPo5qsaRkKggHElIJw8YMkLADM55qR8vtQ70MKizJDqn151muGM3VmBqQE96ay0Y49WN5KJAQ5PKWA5xA4v1Oh+qAyNXqkebzLvoOEhSmHP5FT0sbEfnx0AXXpFLEx+QIDAQABAoIBAGuVc5VyYqx+n5RvSvnetEvL7cFBoB5d3uiGEZNtk7fOR2c9wS0/wfXYJJDnoapVh30hh2ah+g+qUXNlmM3xIlRWTv4unL5q1TD1mOliCT0U6wA1/nv8O759yERSW0cazTI7Rb4Y8OmKqc4qKggwGZ53QgMVGQNMyZWlJohIWK1Klo8X+oyp97TsL4/OZI4sAHTMvEGhEaSgk75swBCLd+NtA/oj5g5gtPnEZIUg5wi5Xmz21FTx0VOQ+BBO4eayw/WGIwGqPCMWm7InfoLG/Z0DUTKlW1hMw5l7ZVpTlTfKvnlidLcD2GfqB7NldXzCajnNL0fL3msje/YgwL5F2RUCgYEA3tQjyhY1IxId8f+0H1R7fO/E7EDLeKb7OjuQ5iAE2uhv/qI/AQ6L6Ike/hMr7bPPZQJq3wyzpbre7um898tMwiUTSgALOBfmQaw6yxDGzFK2HEgTiiwD165hI78aoZpgyOc1KuUo7978FhBzNGBhPb72xl3gzDuG/GqMFtvTHTsCgYEAu7qlselPbOcDUbkcTk6m8FW/aF9B1H5hfg5Ite4j3GPYt6u3i6iKg6GgyB8v3VW0UkL0Dyn1G1SOuXotZHpq/jMwkzEBlkknvP22vGgrAMW8vE6Ocpfw1l+ocQh2OBKlUJmS68a2oMi058r5VF9gZs9LsQreuvZlHMhJ9YpsilsCgYBEo9ySd9zOfNo0nawCqqePiNyEWkFTXTyuZ4LvIJXeSROWwKvfy3dVnkepxIYXpvgQCXqaUvNT7giWV/IZ8somU/1pIjJSiSoouMEzpGtYrXHjrGA4g+57FgBeXpP5i/CccnxyRj0iBvJoaZDTZY0O1DB4UprYzs16G+gjcnvJ2QKBgQCdkPCIHnqCvndDvaorc8qciGwqj2FymEz9/8E5qlLBJbD9oIxjFSiFiUCpF7wV+1xpezbcD2xh7xwIQ6sb3cA18gNAV+6sFGTdNNqO0qPddxqNtyXKuwry88Eudlq7f7LvrqbpbJVW6H8m2m9hSNhiEnXGeIgvxqVhfh7Nj8zAZwKBgQDWr4mL05eEVCcgp+Q4vIy73qXdiuLUOS4gZEurMf6TL/kKQTrr9WbodVuQ+1MMbJ1KViipe28XyaaR3ufh/L578P/n3MECOVqjo4wF2MjLMOvoA/lKGNFf4WUDY78k1Bm65E+z7pQtx0pdCbiL+fJMvfEkNus90Zdp0brPcFElXg==';
        // 正式
        $options->merchantPrivateKey = 'MIIEpAIBAAKCAQEA7BEuGBUOghlo1aUNESYCAf5MVnIaI4fhhrWsj0fAg7T3CSHo16X6tBrwTDp/ihoiSP8//K89CwwJspb5746bvX4bdMbiP2ew4LXKPy7gXppr7IjErRd5mNUfdgyR68JVCZU0Pc/ED5UZu8rVEWPAP35cP7PUH6j5RWPn2aW7dEg0jTk7dj0DoSXi5kWmEHuMJpT9gnHujWHscp7/56jeu9qs9rA8MD91fWHIOdxmLhrOBoNDKo4yQW9I4gF/lidrcZUlA37X7WuxpOMy9Rcs96Hs1opyRHYjHx7o38m7zW/t1KLNOBrzkjFZMExERjMVt/FeMMExsKwiza+AbXBxKQIDAQABAoIBAQDk72BZYIpaZ1QA5Xk/qTGHR1w29Z8d2BhCMQvxRC+SSzzMMYODJ2dzpTBnbM/lWaToUAp4/mVfwkPhW4N17EJjSdpMrNgbj2687e/+yHI1rOJ/WMAL8Vkue3lwXY5iKmuXyIgKbeSBDCMRuQpbO/bkXKIP8VEFhzR/M/1UWOLrR4Ib0HY+qHOHd4nlUYQ6CAdPRKuPSxf4g0zJ6UlIlhcdF3GESljj1dO9hhODF+whkR14UyKWo7/xdJVtMfuRzSGB/LTDjYOomLYN6PHax4Ralxxx2XrAnM9rrFteMz7hLeWeC3jUo2TKLC9D7BJd2Ps44kB/c+ugAUu6bCP82oSlAoGBAPxqw2BC02fotzPpI0xmd8xj0GCQkV5vKJhS0Wz7yqYiBc0aP8QREIQl8Y7fkeYwpQ46taL0N7z9UrvxWzOTQ2I6v/o0pOz6kl3aDJ1TPlGk477hPI43ARRE9Ng7uKefQtQiqfxTv325CtOaSqC+Gv8u/F0aEyDFmHbIK3x8YcB/AoGBAO9rARVveTYnWxtJeRKFgDmt9ccvc+r5/v/pmHMMm/ObSppZWC4tX+546CJGutlGLaZmB0DI3NNorKwwcQd2L584VIehJVCXfK/99tABX/WYvdZGg8LlCguPOfunRDNLRkRzjjf4FLV6eanfacU6rDy6V0qCjpZ/y2ipEHQvxPpXAoGAKeqExUBzoMa5XYpyjSZa8HsGyHJZYYguApWGJ4XskggGVJfuTN3Uk01FvscLkGE1l5ZSQVwywVSD36fl0Du9Ldu5s4/4b03w7lreS2XebGpoU3NNvgQOTtJgomPQdy1wSI/1EGzL2NHYpWjyyZyoGQYxbDh/QqrtdZQp/IMfLHsCgYAH1RhT+zGb8+2nFuA/Gt73BBnmSkcgiM0u0hWKrf7sGUh3hDS+Tf21b38on+e88+7KYswZ8nhG8kWz3GHWPKeSLeWWCk+OM2aA224Xn/PjtPuA5w2ocpXBiw36jZ5Nj0jdNGqSqisDRN52EDkFmsXHttDkPyErC0M8SUuggW+QQQKBgQDfuiJh8zHdPxNngNpYhg/UxbaDODzUV78VkFbhYfsHNl1z6IMRxWGIgl4HLo1CBO+Ij+heicjcQGCp2OFi+ELX2nsbp0iy7xw7ISStFn1g8GIfACdFSauBueezrgLqZYkJBhSIyRJ73UFK8X6TWg1xIIRoM3iML0XDYLZMWN+gMQ==';
        // $options->alipayCertPath = '<-- 请填写您的支付宝公钥证书文件路径，例如：/foo/alipayCertPublicKey_RSA2.crt -->';
        // $options->alipayRootCertPath = '<-- 请填写您的支付宝根证书文件路径，例如：/foo/alipayRootCert.crt" -->';
        // $options->merchantCertPath = '<-- 请填写您的应用公钥证书文件路径，例如：/foo/appCertPublicKey_2019051064521003.crt -->';

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        // 沙箱
        // $options->alipayPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr7R5CBILosVyuGTkMPRahOzF15PVR1Q+tHdfSz/kIRnNQBltxdHNNT95D0j31d+045QKyNz2gmzCn9PA6o/euSfLXyd/tjZo7Q06pZRBT3xgTWz12+bzmq6ZeDiUMNyLe0GejFQA/Zhm8wa0lKfmopCtI8sJyWX23r9Tbaqtg0Fe8BOAbDXOQjMViqEZ/0gb+4ZnsDXp8DY0if10UPgFOu18o28PiHWlWXgHvPThuthsb/p+UVTxtIlQc7f7cYfzlHF5KLpsrlv4i57zsdEmsdE7kKiBo2Q2du0HafALdKBNfaT4VM14/uz/DtH7Eh7iTtQEu6w6dGBflQ7OZO4sOQIDAQAB';
        // 正式
        $options->alipayPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAi/7veDoX8BCxJ7QUpBlrsZcZnsr30WMHCpnKFnJGHqXXKGUnp0rNZkBw7t1s73gAHmzWMWAssLy0ZdZR+WDFy4loAx/aTyyAVmwxjX6QJLYh5vM9CRfnD7JkGYqaVIFx2jdywVabn8SPk5VujMdR45Q41n2oxsY0RLYQVGtyi87gUegiMXptax5AiO2ysziqH8vKkmU7ALeKgtChvA2b9Ki4vnuVHUZIcx/2t0/xWUdDhl42I5w9lH9adRAE/JoNJNrk7bGHvHcYWpa+4Nng7SJC5mj57yuyagQGD7Xh/CPoR4AqZdbgqXo9uxn76DUvfFL3dcOPu0SQRAzgwhWeFwIDAQAB';
        //可设置异步通知接收服务地址（可选）
        // $options->notifyUrl = "<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->";
        $options->notifyUrl = Yii::$app->params['ali_notify'];

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        // $options->encryptKey = "<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->";


        return $options;
    }

    /**
     * 创建支付订单
     * @param string $subject 订单标题
     * @param string $outTradeNo 交易创建时传入的商户订单号
     * @param string $totalAmount 订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
     * @return \Alipay\EasySDK\Payment\App\Models\AlipayTradeAppPayResponse
     */
    public function pay($subject, $outTradeNo, $totalAmount)
    {
        $ttl = date('Y-m-d H:i:s', time() + 120); // 设置订单超时时间120s
        Factory::payment()->app()->optional('time_expire', $ttl);
        return Factory::payment()->app()->pay($subject, $outTradeNo, $totalAmount);
    }

    /**
     * 验签
     * @param $parameters
     * @return bool
     */
    public function verify($parameters)
    {
        return Factory::payment()->common()->verifyNotify($parameters);
    }

    /**
     * 查询订单信息
     * @param string $outTradeNo 外部交易订单号
     * @return \Alipay\EasySDK\Payment\Common\Models\AlipayTradeQueryResponse
     */
    public function query($outTradeNo)
    {
        return Factory::payment()->common()->query($outTradeNo);
    }

    /**
     * 退款
     * @param $outTradeNo
     * @param $refundAmount
     * @return \Alipay\EasySDK\Payment\Common\Models\AlipayTradeRefundResponse
     */
    public function refund($outTradeNo, $refundAmount)
    {
        return Factory::payment()->common()->refund($outTradeNo, $refundAmount);
    }
}