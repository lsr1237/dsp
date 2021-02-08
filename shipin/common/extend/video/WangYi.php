<?php

namespace common\extend\video;


class WangYi
{
    public static function createParam($text)
    {
        $key = '0CoJUm6Qyw8W8jud';
        $module = 'dcf734dbca8108164eb3b237f79b1945fbd63232c3d6b84aeef5c15ab5dad28fbc30bb3aa1ef9484b7a0ec69dcc85d4c77bda1f9d788713d730f6cee31b9d8a8302791b95822a60d51681cd9fd74043aa0d50a57707190db6ff59658034066286754a1bb0c2a3253c3fcf2dab7b4be9d33f62507c1ad3dd78561c75a69b5191f';
        $enStr = self::NetEaseMusicAES($text,$key);
        $key2 = 'wbPl0UucSzEeGlKO';
        $params = WangYi::NetEaseMusicAES($enStr,$key2);
        $data['params'] = $params;
        $data['encSecKey'] = $module;
        return $data;
    }


    public static function netEaseMusicAES($text, $key, $iv = '0102030405060708')
    {
        $text = trim($text);
        $pad = 16 - strlen($text) % 16;
        $chr = chr($pad);
        $text = $text . str_repeat($chr, $pad);
        // $enStr = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_CBC, $iv);
        $enStr =  openssl_encrypt($text, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $enStr = base64_encode($enStr);
        return $enStr;
    }


    public static function curl($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function convertUrlArray($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }
}