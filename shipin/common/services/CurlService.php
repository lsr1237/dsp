<?php

namespace common\services;

class CurlService
{
    /**
     * 获取远程数据
     * @param string $url
     * @param string|array $data
     * @param string|array $header
     * @param string $encoding
     * @param boolean $isReturnHttpCode 是否返回httpCode
     * @param integer $timeout 超时时间（单位：秒）
     * @param null|array $sslSet ssl相关证书等配置
     * @param bool $returnHeader 是否返回头部验签
     * @return mixed
     */
    public static function sendRequest($url, $data = '', $header = '', $encoding = '', $isReturnHttpCode = false, $timeout = 60, $sslSet = null, $returnHeader = false)
    {
        $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11";
        $contents = '';
        $url = htmlspecialchars_decode($url);
        if (function_exists('curl_init') && @$ch = curl_init()) {
            // 获取远程文件内容
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            if (!empty($header)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            if (empty($sslSet)) {
                curl_setopt($ch, CURLOPT_SSLVERSION, 0); // 设定SSL版本
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslSet['verifypeer']);
                curl_setopt($ch, CURLOPT_CAINFO, $sslSet['cainfo']);
                curl_setopt($ch, CURLOPT_SSLCERT, $sslSet['sslcert']);
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $sslSet['sslcert_password']);
            }
            if (!empty($encoding)) {
                curl_setopt($ch, CURLOPT_ENCODING, $encoding);
            }
            // curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:10809');
            if ($isReturnHttpCode) {
                $contents = [];
                $contents['content'] = curl_exec($ch);
                $contents['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            } elseif ($returnHeader) {
                curl_setopt($ch, CURLOPT_HEADER, $returnHeader); // 获取头部信息
                $contents = [];
                $str = curl_exec($ch);
                $lenth = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headerArr = explode("\r\n", substr($str, 0, $lenth));
                foreach ($headerArr as $val) {
                    $arr = explode(':', $val);
                    if ($arr[0] == 'X-UD-Signature') {
                        $contents['signature'] = $arr[1];
                    }
                }
                $contents['content'] = substr($str, $lenth);
            } else {
                $contents = curl_exec($ch);
            }
            curl_close($ch);
        }
        return $contents;
    }
}