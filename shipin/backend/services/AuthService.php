<?php
namespace backend\services;

use Yii;
use yii\helpers\Json;
use backend\bases\BackendService;
use backend\models\AuthItemChild;

class AuthService extends BackendService
{

    /**
     * 添加角色默认权限
     */
    public static function assignDefaultAuth($role)
    {
        $defaultAuth = ['/admin/basic', '/menu/mine' ,'/site/index' ,'/site/logout'];

        $inserts = [];
        foreach ($defaultAuth as $auth) {
            $inserts[] = [$role, $auth ];
        }
        Yii::$app->db
            ->createCommand()
            ->batchInsert('auth_item_child', ['parent', 'child'], $inserts)
            ->execute();

    }
}

