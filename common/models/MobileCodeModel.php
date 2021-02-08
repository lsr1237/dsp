<?php

namespace common\models;

use common\bases\CommonModel;

class MobileCodeModel extends CommonModel
{
    const EFFECTIVE_TIME = 15; // 有效时间15分钟

    /**
     * 添加短信验证码/动态密码（二者长度一致,均为6位）
     * @param string $mobile 手机号码
     * @param string $code 短信验证码/动态密码
     * @return boolean
     */
    public static function addMobileCode($mobile, $code)
    {
        $model = new MobileCode();
        $model->mobile = $mobile;
        $model->code = $code;
        $time = time();
        $model->created_at = $time;
        $model->expire_time = $time + (self::EFFECTIVE_TIME * 60);
        return $model->save();
    }

    /**
     * 校验手机验证码/动态密码
     * @param string $mobile 手机号
     * @param string $code 验证码/动态密码
     * @return boolean
     */
    public static function checkMobileCode($mobile, $code)
    {
        $model = MobileCode::find();
        $model->andWhere(['mobile' => $mobile]);
        $model->andWhere(['code' => $code]);
        $model->andWhere(['>', 'expire_time', time()]);
        return (bool)$model->count();
    }

    /**
     * 校验是否存在未过期的验证码
     * @param string $mobile 手机号
     * @return bool
     */
    public static function checkHasValidCode($mobile)
    {
        $model = MobileCode::find();
        $model->andWhere(['mobile' => $mobile]);
        $model->andWhere(['>', 'expire_time', time()]);
        return (bool)$model->count('id');
    }
}