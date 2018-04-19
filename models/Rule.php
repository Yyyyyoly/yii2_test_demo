<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "rule".
 *
 * @property int $id
 * @property string $action 路由规则
 * @property string $rule_description 规则说明
 *
 * @property UserRule[] $userRules
 * @property User[] $users
 */
class Rule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['action', 'rule_description'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'action' => 'Action',
            'rule_description' => 'Rule Description',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserRules()
    {
        return $this->hasMany(UserRule::className(), ['rule_Id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])->viaTable('user_rule', ['rule_Id' => 'id']);
    }
}
