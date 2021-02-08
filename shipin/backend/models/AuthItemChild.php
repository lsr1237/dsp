<?php

namespace backend\models;

/**
 * This is the model class for table "auth_item_child".
 *
 * @property string $parent
 * @property string $child
 */
class AuthItemChild extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'auth_item_child';
    }

    public function rules()
    {
        return [
            [['parent', 'child'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'parent' => 'Parent',
            'child' => 'Child',
        ];
    }
}