<?php
namespace backend\controllers;

use backend\models\LoginLogModel;
use backend\models\AdminModel;
use common\extend\wx\AppletConfig;
use common\models\Uploader;
use backend\services\AdminService;
use common\extend\utils\IPUtils;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Json;
use backend\models\LoginForm;
use backend\bases\BackendController;

/**
 * Site controller
 */
class SiteController extends BackendController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'logout',  'captcha'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'csrf', 'upload', 'upload-apk', 'upload-ipa', 'upload-plist', 'user-app-location', 'applet'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['get'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions() 
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'maxLength' => 4,
                'minLength' => 4,
                'width' => 100,
                'height' => 35,
                'offset' => 10,
                'testLimit' => 999
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->redirect('/vue-dist/#/');
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {
                $isLock = AdminService::isLock($model->getUser()); // 是否锁定
                if ($isLock['ret']) {
                    Yii::$app->session->setFlash('error', $isLock['msg']);
                } else {
                    $loginIp = IPUtils::getUserIP();
                    LoginLogModel::add([
                        'admin_id' => Yii::$app->user->id,
                        'login_ip' => $loginIp,
                    ]); // 保存登录记录
                    AdminModel::update(Yii::$app->user->id, [
                        'login_ip' => $loginIp,
                        'login_time' => date('Y-m-d H:i:s'),
                        'state' => AdminModel::SIGNED,
                        'err_at' => null,
                        'err_cnt' => 0,
                    ]); // 更新最后一次登录IP、更新错误次数
                    setcookie('admin_csrf', Yii::$app->request->getCsrfToken(), 0, '/');
                    return $this->redirect('/');
                }
            } else {
                $isLock = AdminService::isLock($model->getUser()); // 是否锁定
                if ($isLock['ret']) {
                    $msg = $isLock['msg']; // 错误信息
                } elseif ($model->getPwdErr()) {
                    $msg = AdminService::accountLock($model->getUser()); // 错误信息
                } else {
                    $msg = $model->getMsg(); // 获取提示信息
                };
                Yii::$app->session->setFlash('error', empty($msg) ? '验证码错误' : $msg);
            }
        }
        return $this->renderPartial('login', [
            'model' => $model,
            'error' => Yii::$app->session->getFlash('error'),
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['/site/login']);
    }

    /**
     * 图片上传
     * @return string
     */
    public function actionUpload()
    {
        $config = [
            'pathFormat' => '/dspjj/{yyyy}/{time}{rand:6}',
            'maxSize' => 1024*1024*2,
            'allowFiles' => ['.jpeg', '.png', '.jpg'],
            'uploadFilePath' => '/data/images/',
        ];
        if (Yii::$app->request->isPost) {
            $model = new Uploader('file', $config,'');
            if ($model) {
                $result = $model->getFileInfo();
                return Json::encode([
                    'id' => 101,
                    'url' => '/data/images'.$result['url'],
                ]);
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '上传失败']);
    }

    /**
     * 上传apk文件
     * @return string
     */
    public function actionUploadApk()
    {
        if (empty($_FILES)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选apk文件']);
        }
        // 解析包获得名称 包名-版本名-版本号.apk
        $temFilePath = $_FILES['file']['tmp_name']; // 上传apk临时文件
        if (file_exists($temFilePath)) {
            $newName = 'dspjj';
            $softUploadPath = Yii::$app->params['soft_upload_path'] ?? '';
            $softDownloadUrl = Yii::$app->params['soft_download_url'] ?? '';
            $config = [
                "pathFormat" => '/'.$newName,
                "maxSize" => 1024*1024*60, // 小于60M未知
                "allowFiles" => ['.apk'], // 只允许扩展名为.apk的文件上传
                'uploadFilePath' => $softUploadPath,
            ];
            if (Yii::$app->request->isPost) {
                $model = new Uploader('file', $config, '');
                $result = $model->getFileInfo();
                if ($result['state'] == self::STATUS_SUCCESS) {
                    return Json::encode([
                        'status' => self::STATUS_SUCCESS,
                        'url' => $softDownloadUrl.$softUploadPath.$result['url'],
                    ]);
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $result['state']]);
                }
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '上传失败']);
    }

    /**
     * 上传ipa文件
     * @return string
     */
    public function actionUploadIpa()
    {
        if (empty($_FILES)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选ipa文件']);
        }
        $temFilePath = $_FILES['file']['tmp_name']; // 上传ipa临时文件
        if (file_exists($temFilePath)) {
            $newName = 'dspjj';
            $softUploadPath = Yii::$app->params['soft_upload_path'] ?? '';
            $softDownloadUrl = Yii::$app->params['soft_download_url'] ?? '';
            $config = [
                "pathFormat" => '/'.$newName,
                "maxSize" => 1024*1024*60, // 小于60M未知
                "allowFiles" => ['.ipa'], // 只允许扩展名为.ipa的文件上传
                'uploadFilePath' => $softUploadPath,
            ];
            if (Yii::$app->request->isPost) {
                $model = new Uploader('file', $config, '');
                $result = $model->getFileInfo();
                if ($result['state'] == self::STATUS_SUCCESS) {
                    return Json::encode([
                        'status' => self::STATUS_SUCCESS,
                        'url' => $softDownloadUrl.$softUploadPath.$result['url'],
                    ]);
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $result['state']]);
                }
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '上传失败']);
    }

    /**
     * 上传plist文件
     * @return string
     */
    public function actionUploadPlist()
    {
        if (empty($_FILES)) {
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选plist文件']);
        }
        $temFilePath = $_FILES['file']['tmp_name']; // 上传plist临时文件
        if (file_exists($temFilePath)) {
            $newName = 'manifest';
            $softUploadPath = Yii::$app->params['soft_upload_path'] ?? '';
            $softDownloadUrl = Yii::$app->params['soft_download_url'] ?? '';
            $config = [
                "pathFormat" => '/'.$newName,
                "maxSize" => 1024*1024*60, // 小于60M未知
                "allowFiles" => ['.plist'], // 只允许扩展名为.plist的文件上传
                'uploadFilePath' => $softUploadPath,
            ];
            if (Yii::$app->request->isPost) {
                $model = new Uploader('file', $config, '');
                $result = $model->getFileInfo();
                if ($result['state'] == self::STATUS_SUCCESS) {
                    return Json::encode([
                        'status' => self::STATUS_SUCCESS,
                        'url' => $softDownloadUrl.$softUploadPath.$result['url'],
                    ]);
                } else {
                    return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $result['state']]);
                }
            }
        }
        return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '上传失败']);
    }

    /**
     * 获取小程序名数组
     * @return string
     */
    public function actionApplet()
    {
        foreach (AppletConfig::APPLET_NAME as $key => $row) {
            $data[] = [
                'key' => $key,
                'name' => $row['name']
            ];
        }
        return self::success(['results' => $data]);
    }
}
