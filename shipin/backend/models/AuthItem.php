<?php

namespace backend\models;

/**
 * This is the model class for table "auth_item".
 *
 * @property string $name
 * @property string $rule_name
 * @property string $description
 * @property string $data
 * @property string $p_name
 * @property integer $type
 * @property integer $created_at
 * @property integer $updated_at
 */
class AuthItem extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'auth_item';
    }

    public function rules()
    {
        return [
            [['name', 'rule_name', 'p_name'], 'string', 'max' => 64],
            [['type', 'created_at', 'updated_at'], 'integer'],
            [['description', 'data'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Name',
            'type' => 'Type',
            'data' => 'Data',
            'description' => 'description',
            'rule_name' => 'Rule Name',
            'p_name' => 'P Name',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
        ];
    }
}
