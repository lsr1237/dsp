<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
/* @var $this \yii\web\View */
/* @var $content string */
$this->registerJsFile('@web/js/admin_js/index.js', ['depends' => 'backend\assets\AppAsset', 'position' => View::POS_HEAD]);
$this->registerJsFile('@web/Plug-in/My97/WdatePicker.js', ['depends' => 'backend\assets\AppAsset', 'position' => View::POS_HEAD]);
$this->registerJsFile('@web/Plug-in/layer/layer.min.js', ['depends' => 'backend\assets\AppAsset', 'position' => View::POS_HEAD]);
$this->registerCssFile('@web/css/adm-lte/bootstrap/bootstrap.min.css');
$this->registerCssFile('@web/css/adm-lte/AdminLTE.min.css');
$this->registerCssFile('@web/css/adm-lte/skins/_all-skins.min.css');
$this->registerCssFile('@web/css/adm-lte/font-awesome.min.css');
$this->registerCssFile('@web/css/admin_css/style.css');
$this->registerCssFile('@web/Plug-in/layer/skin/layer.css');
$this->registerJsFile('@web/js/admin_js/common.js', ['depends' => 'backend\assets\AppAsset', 'position' => View::POS_HEAD]);
$this->registerJsFile('@web/js/adm-lte/jquery.slimscroll.min.js', ['depends' => 'backend\assets\AppAsset', 'position' => View::POS_HEAD]);
$this->title = '管理后台 - 短视频剪辑';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
<meta charset="<?= Yii::$app->charset ?>">
<title><?= Html::encode($this->title) ?></title>
<!--http://xu.sentsin.com/jquery/layer/ -->
<?= Html::csrfMetaTags() ?>
<?php $this->head() ?>
</head>

<body class="skin-yellow sidebar-mini fixed">
<div class="wrapper">
    <!-- header -->
    <header class="main-header">
        <a class="logo">
            <span class="logo-mini"><b>S</b>p</span>
            <span class="logo-lg"><b>S</b>p</span>
        </a>
        <nav class="navbar navbar-static-top">
            <a href="javascript:;" class="sidebar-toggle" onclick="sidebarToggle()"> </a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown user user-menu" style="background: none;">
                        <a class="dropdown-toggle">
                            <img src="/images/profile_small.jpg" class="user-image" alt="User Image">
                            <span class="hidden-xs user_name"></span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= Url::to(['site/logout']) ?>"><i class="fa fa-power-off"></i></a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <!-- header -->
    <!-- menu -->
    <aside class="main-sidebar" style="z-index: 8;">
        <section class="sidebar">
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="/images/profile_small.jpg" class="img-circle" alt="User Image">
                </div>
                <div class="pull-left info">
                    <p class="user_name"></p>
                    <a href="javascript:;"><i class="fa fa-circle text-success"></i> 在线</a>
                </div>
            </div>
            <ul class="sidebar-menu" id="sidebar-menu">
                <li class="header">欢迎您！</li>
            </ul>
        </section>
    </aside>
    <!-- menu -->
    <!-- wrap -->
    <div class="content-wrapper" style="background-color: #fff; min-height: 500px;">
        <section id="nav_breadcrumbs" class="content-header">
            <h1>后台管理</h1>
            <ol class="breadcrumb">
                <li>
                    <a><i class="fa fa-dashboard"></i> 后台管理</a>
                </li>
            </ol>
        </section>
        <!-- content -->
        <div class="routerView" style="padding: 10px 15px 15px;">
            <?php $this->beginBody() ?>
            <div id="MainBox" class="admin_content">
                <?= $content ?>
            </div>
            <?php $this->endBody() ?>
        </div>
        <!-- content -->
    </div>
    <!-- wrap -->
</div>

<script type="text/javascript">
    function sidebarToggle() {
        var obj = document.body;
        var clsArr = obj.className.split(' ');
        obj.className = clsArr.length !== 4 ? obj.className + ' sidebar-collapse' : clsArr[0] + ' ' + clsArr[1] + ' ' + clsArr[2];
    }
    function sidebar() {
        $('.sidebar').slimscroll({
            height: ($(window).height() - $('.main-header').height()) + 'px',
            size: '3px',
            color: 'rgba(0,0,0,0.2)',
        });
    }
    $(window).resize(function resizeFunc() {
        sidebar();
    });
    sidebar();
    $(function() {
        layer.load(2);
        $.get('<?= Url::to(['admin/basic']) ?>', function (uObj) {
            $.get('<?= Url::to(['menu/mine']) ?>', function (mObj) {
                $('.user_name').text(uObj.results[0].name);
                for (var key in mObj.results) {
                    var topMenu = mObj.results[key];
                    var menus = '';
                    var active = false;
                    for (var k in topMenu.children) {
                        var menu = topMenu.children[k];
                        var act = menu.route == window.location.pathname;
                        menus += '<li' + (act ? ' class="active"' : '') + '><a href="' + menu.route + '"><i class="fa fa-circle-o"></i>' + menu.title + '</a></li>';
                        if (act) {
                            active = true;
                            $('#nav_breadcrumbs h1').text(menu.title);
                            $('#nav_breadcrumbs ol').append('<li>' + topMenu.title + '</li>');
                            $('#nav_breadcrumbs ol').append('<li class="active">' + menu.title + '</li>');
                        }
                    }
                    $("#sidebar-menu").append('<li class="treeview' + (active ? ' active' : '') + '"><a href="javascript:;"><i class="fa '
                        + topMenu.icon + '"></i><span>' + topMenu.title + '</span><span class="pull-right-container">'
                        + '<i class="fa fa-angle-left pull-right"></i></span></a><ul class="treeview-menu">' + menus + '</ul></li>');
                }
                $('.treeview').click(function clickFunc() {
                    var $this = $('>a', this);
                    var checkElement = $this.next('ul');
                    var animationSpeed = 500;
                    if ((checkElement.is(':visible')) && (!$('body').hasClass('sidebar-collapse'))) {
                        checkElement.slideUp(animationSpeed, function() {
                            checkElement.removeClass('menu-open');
                        });
                        checkElement.parent('li').removeClass('active');
                    } else if (!checkElement.is(':visible')) {
                        var parent = $('.sidebar-menu');
                        var ul = parent.find('ul:visible').slideUp(animationSpeed);
                        ul.removeClass('menu-open');
                        checkElement.slideDown(animationSpeed, function() {
                            checkElement.addClass('menu-open');
                            parent.find('li.treeview').removeClass('active');
                            $this.parent('li').addClass('active');
                        });
                    }
                });
                layer.closeAll();
            }, 'json').fail(function () {
                layer.closeAll();
            });
        }, 'json').fail(function () {
            layer.closeAll();
        });
    });
</script>
</body>
</html>
<?php $this->endPage() ?>
