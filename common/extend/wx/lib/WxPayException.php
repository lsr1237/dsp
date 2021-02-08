<?php

namespace  common\extend\wx\lib;
/**
 *
 * 微信支付API异常类
 * @author widyhu
 *
 */
use yii\base\Exception;
class WxPayException extends Exception {
    public function errorMessage()
    {
        return $this->getMessage();
    }
}
