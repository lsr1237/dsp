<?php
namespace common\bases;
use yii\base\Model;
class CommonForm extends Model{
    public $message;	//提示消息
    public $rUrl = null;	//跳转地址
    public $code = null;	//操作成功失败码
    
	/**
     * 表单提交
     */
    public function submit(){
        if(\Yii::$app->request->getIsPost()){
            return $this->load(\Yii::$app->request->post());
        }elseif(\Yii::$app->request->getIsGet()){
            return $this->load(\Yii::$app->request->get());
        }
    }
    
    /**
     * 表单成功提示操作
     */
    public function success($param){
        $this->code = 1000;
        if(!empty($param['message'])){
            $this->message = $param['message'];
        }else{
            $this->message = '操作成功！';
        }
        if(isset($param['rUrl']) and !empty($param['rUrl'])){
            $this->rUrl = $param['rUrl'];
        }else{
            $this->rUrl= null;
        }
        return true;
    }
    
    /**
     * 表单失败操作
     */
    public function fail($param){
        $this->code = 2000;
        if(!empty($param)){
            if(is_array($param)){
                $para = current($param);
                if(is_array($para)){
                    $this->message = current($para);
                }else{
                    $this->message = $para;
                }
            }else{
                $this->message = $param;
            }
        }else{
            $this->message = '操作失败！';
        }
        return false;
    }
    
    /**
     * 前端显示提示操作
     */
    public function showSummary($view){
        if($this->code !== null){
            $view->registerJs('alert("'.$this->message.'")');
            if($this->rUrl !== null){
                $view->registerJs('window.location.href="'.$this->rUrl.'"; ');
            }
        }
    }

}