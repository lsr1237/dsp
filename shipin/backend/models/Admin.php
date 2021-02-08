<?php

namespace backend\models;

use Yii;
use backend\models\AuthAssignment;

use yii\web\IdentityInterface;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;

/**
 * This is the model class for table "admin".
 *
 * @property int $id
 * @property string $username 管理员名称
 * @property string $password 密码
 * @property string $real_name 真实姓名
 * @property int $state 状态
 * @property string $login_ip 登入IP
 * @property string $login_time 登入时间
 * @property string $err_at 最后一次密码错误时间
 * @property int $err_cnt 密码错误累积次数
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Admin extends \yii\db\ActiveRecord implements IdentityInterface
{
    private $_routes = null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['state', 'err_cnt'], 'integer'],
            [['login_time', 'err_at', 'created_at', 'updated_at'], 'safe'],
            [['username'], 'string', 'max' => 30],
            [['password'], 'string', 'max' => 64],
            [['real_name'], 'string', 'max' => 50],
            [['login_ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'real_name' => 'Real Name',
            'state' => 'State',
            'login_ip' => 'Login Ip',
            'login_time' => 'Login Time',
            'err_at' => 'Err At',
            'err_cnt' => 'Err Cnt',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getRole()
    {
        return $this->hasOne(AuthAssignment::className(), ['user_id' => 'id']);
    }

    public function validatePassword($password){
        if(empty($password)){
            return false;
        }else{
            try {
                if($this->password != md5($password) && !Yii::$app->getSecurity()->validatePassword($password, $this->password)){
                    return false;
                }
            } catch (InvalidParamException $e) {
                Yii::error($e);
                return false;
            }
        }
        return true;
    }
    public static function findByUsername($username)
    {
        return self::findOne(['username'=>$username]);
    }

    public static function findIdentity($userid)
    {
        return static::findOne(['id' => $userid]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return true;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function getRoutes()
    {
        if (is_null($this->_routes)) {
            $this->_routes = array_keys(Yii::$app->authManager->getPermissionsByUser($this->id));
        }
        return $this->_routes;
    }
}
