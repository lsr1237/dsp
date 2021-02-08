<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;
use yii\helpers\Url;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>后台登陆-短视频剪辑</title>
<link rel="stylesheet" type="text/css" href="/css/admin_css/reset.css"/>
<link rel="stylesheet" type="text/css" href="/css/admin_css/style.css"/>
</head>
<body>
       <!-- 背景 -->
        <div id="login_bg"><img src="/images/admin_img/login_bg4.jpg" width="1920" height="1080" alt="背景" /></div>
        <!--登陆 -->
       <div id="lay">
        <div id="login_box">
                <?php $form = ActiveForm::begin(['id' => 'login-form','fieldConfig'=>['template'=>"{input}"],'options'=>['onsubmit'=>'return Check()']]); ?>
                <div class="b">
                    <h2 style="font-size: 24px; color: #ffffff">账号密码登陆</h2>
                    <div class="user"><?= $form->field($model, 'username')->textInput(['id'=>"user",'value'=>'请输入用户名']) ?></div>
                    <div class="psw"><?= $form->field($model, 'password')->passwordInput(['id'=>'psw','value'=>'', 'placeholder' => '请输入密码']) ?></div>
                        <div class="verifyCode"><?= $form->field($model, 'verifyCode')->textInput(['id'=>'verifyCode','value'=>'请输入验证码']) ?>
                            <?= $form->field($model, 'captcha')
                                ->widget(Captcha::className(),[   
                                   'template' => "{image}",
                                   'imageOptions' => ['title'=>'点击换图', 'alt' => '验证码', 'style' => 'cursor:pointer; width:100%; height:100%; vertical-align:middle'],
                            ]); ?>
                        </div>
                    <p class="tc"><?= Html::submitButton('', ['class' => 'submit', 'name' => 'login-button']) ?></p>
                </div>
                <?php ActiveForm::end(); ?>
                <div class="c"><span><?= $error ?? '' ?></span></div>
        </div>  
      </div>
        <!--/登陆 -->
<script type="text/javascript" src="/js/jquery-1.8.3.js"></script> 
<script type="text/javascript" src="/js/admin_js/login.js"></script>
<script type="text/javascript">
    $(function(){
        refreshCode();
        $("#loginform-captcha-image").click(function(){
            refreshCode();
        });
    });
    function refreshCode() {
        $.get('<?= Yii::$app->urlManager->createUrl('site/captcha'); ?>',{refresh:1},function(data){
            var evaldata = eval(data);
            $("#loginform-captcha-image").attr('src',evaldata.url); 
        });
    }
</script>
<!--[if IE 6]>
<script type="text/javascript" src="js/DD_belatedPNG_0.0.8a-min.js" ignoreapd="1"></script>
<script>
DD_belatedPNG.fix('#login_box');
</script>
<![endif]-->
</body>
</html>