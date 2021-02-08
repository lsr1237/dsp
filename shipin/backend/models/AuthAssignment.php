<?php

namespace backend\models;

/**
 * This is the model class for table "auth_assignment".
 *
 * @property string $item_name
 * @property integer $user_id
 * @property integer $created_at
 */
class AuthAssignment extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'auth_assignment';
    }

    public function rules()
    {
        return [
            [['user_id', 'created_at'], 'integer'],
            [['item_name'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'item_name' => 'Item Name',
            'created_at' => 'Created at',
        ];
    }
}
