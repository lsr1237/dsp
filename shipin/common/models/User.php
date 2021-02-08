<?php

namespace common\models;

use common\bases\CommonService;
use common\services\RedisService;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "User".
 *
 * @property int $id 用户ID
 * @property int $p_id 邀请者ID
 * @property string $name 姓名
 * @property string $mobile 手机号码
 * @property string $password 密码
 * @property string $openid 微信OPENID
 * @property string $official_openid 微信公众号OPENID
 * @property string $union_id 微信union_id
 * @property int $wx_id 小程序id
 * @property int $state 用户状态 1:正常 2:黑名单
 * @property string $number 会员编号
 * @property int $member_card_id 会员卡ID
 * @property string|null $end_at 会员截止时间
 * @property int $num 可使用次数
 * @property string $created_at 注册时间
 * @property string $updated_at 更新时间
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATE_ACTIVE = 1;
    const STATE_INACTIVE = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            // TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['p_id', 'wx_id', 'state', 'member_card_id', 'num'], 'integer'],
            [['end_at', 'created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 11],
            [['password', 'openid', 'official_openid', 'union_id'], 'string', 'max' => 64],
            [['number'], 'string', 'max' => 10],
            ['state', 'in', 'range' => [self::STATE_ACTIVE, self::STATE_INACTIVE]],
            [['number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($userId)
    {
        return static::findOne(['id' => $userId]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
//        $key = sprintf('%s%s', CommonService::USER_TOKEN_PREFIX, $token);
//        $userId = RedisService::getKey($key);
        $userToken = UserTokenModel::validateAccessToken($token);
        if (!$userToken) {
            return null;
        }
        $user = self::findIdentity($userToken->userid);
        if ($user->state == self::STATE_INACTIVE) {
            // RedisService::delKey($key);
            UserTokenModel::dropUserToken($token);
            return null;
        }
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * @param $password
     * @throws \yii\base\Exception
     */
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    public function getMemberCard()
    {
        return $this->hasOne(MemberCard::class, ['id' => 'member_card_id']);
    }

}
