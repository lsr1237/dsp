<?php

/**
 * 控制器基类
 * BaseController.php
 * @author     addition
 */

namespace common\bases;

use yii\web\Controller;
use yii\helpers\Json;
use Yii;

class CommonController extends Controller 
{
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILURE = 'FAILURE';

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * 获取POST或者GET传递的数据
     * @param 参数名称
     * @return array 
     * @author addition
     */
    public function getParams($param = '', $defaultValue = NULL)
    {
        if ($param != '') {
            $result = \Yii::$app->request->post($param);
            if (empty($result)) {
                $result = \Yii::$app->request->get($param, $defaultValue);
            }
            return trim($result);
        } else {
            $result = \Yii::$app->request->post() or $result = \Yii::$app->request->get();
        }
        return $result;
    }

    /**
     * 返回成功JSON格式
     * author :addition
     * @param string
     * @return JSON
     */
    public function jsonSuccess($message = '操作成功') {
        return json_encode([
            'code' => '1000',
            'message' => $message
        ]);
    }

    /**
     * 返回失败JSON格式
     * tags
     * author :addition
     * @param string
     * @return JSON
     */
    public function jsonFail($message = '操作失败') 
    {
        return json_encode([
            'code' => '2000',
            'message' => $message
        ]);
    }

    /**
     * AR转数组
     * tags
     * @author addition
     * @param unknowtype
     * @return array
     */
    public function objectToArray($object, &$array = [])
    {
        foreach ($object as $value) {
            $array[] = $value->getAttributes();
        }
    }


    /**
     * base64转图片保存
     * @auther: addition
     * @param string
     */
    public function base64_to_img($base64_string, $output_file)
    {
        $ifp = fopen($output_file, "wb");
        
        fwrite($ifp, base64_decode($base64_string));
        fclose($ifp);
        return( $output_file );
    }

    /**
     * @param string $msg
     * @return string
     */
    protected static function err($msg)
    {
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $msg]);
    }

    /**
     * @param array $result
     * @return string
     */
    protected static function success($result = [])
    {
        return Json::encode(array_merge(['status' => self::STATUS_SUCCESS, 'error_message' => ''], $result));
    }

    /**
     * @param $msg
     * @return string
     */
    protected function successMsg($msg)
    {
        return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => $msg]);
    }
}
