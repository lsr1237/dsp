<?php
namespace backend\models;

use Yii;
use common\models\User;
use common\bases\CommonForm;
use backend\models\Admin;
use backend\models\AdminModel;

/**
 * user form
 */
class LoginForm extends CommonForm
{
    public $username;
    public $password;
    public $rememberMe = false;
    public $verifyCode;
    public $captcha;

    private $_user = false;
    private $_msg = '';
    private $_pwd_err = false; // 是否密码错误


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['password'], 'required','message'=>'密码不能为空'],
            [['username'], 'required','message'=>'手机号不能为空'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean','message'=>'输入格式有误'],
            // password is validated by validatePassword()
            ['username', 'validatePassword'],
            ['verifyCode', 'required'],
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user) {
                $this->addError($attribute, $this->getMsg());
            } elseif ($user && !$user->validatePassword($this->password)) {
                $this->_msg = '用户名或密码不正确！';
                $this->_pwd_err = true;
                $this->addError($attribute, $this->getMsg());
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        } else {
            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $user = Admin::findByUsername($this->username);
            $this->_user = $user;
            if (!$user) {
                $this->_user = false;
                $this->_msg = '用户不存在，请联系管理员';
            } elseif ($user && $user->state == AdminModel::RESIGNED) {
                $this->_user = false;
                $this->_msg = '您的账户已被禁止登录，详情请咨询管理员';
            }
        }
        return $this->_user;
    }
    
    public function attributeLabels(){
        return [
            'username'=>'用户名',
            'password'=>'密码'
        ];
    }

    /**
     * 获取提示信息
     * @return string
     */
    public function getMsg()
    {
        return $this->_msg;
    }

    /**
     * 获取密码错误状态
     * @return bool
     */
    public function getPwdErr()
    {
        return $this->_pwd_err;
    }
}
