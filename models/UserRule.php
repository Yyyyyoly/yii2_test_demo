<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_rule".
 *
 * @property int $user_id
 * @property int $rule_Id
 *
 * @property Rule $rule
 * @property User $user
 */
class UserRule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_rule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'rule_Id'], 'required'],
            [['user_id', 'rule_Id'], 'integer'],
            [['user_id', 'rule_Id'], 'unique', 'targetAttribute' => ['user_id', 'rule_Id']],
            [['rule_Id'], 'exist', 'skipOnError' => true, 'targetClass' => Rule::className(), 'targetAttribute' => ['rule_Id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'rule_Id' => 'Rule  ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRule()
    {
        return $this->hasOne(Rule::className(), ['id' => 'rule_Id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }


    /**
     * 获取菜单列表
     * @param $userId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getMenuList($userId){
        $query = self::find()
            ->where(['user_id' => $userId])
            ->one();
        return $query->getRule();
    }


    /**
     * 这里只是写了一个表 链接查询的示例
     * select *
     * from user_rule
     * leftJoin rule on user_rule.rule_id = rule.id（这里我没有写on的条件，但是因为前面申明了外键所以直接读取了）
     * where (user_id = 1 or user_id = 2) and rule_id = 1
     */
    public function getMenu(){
        $query = self::find()
            ->leftJoin('rule')
            ->where(['or','user_id=1', 'user_id=2'])
            ->andWhere(['rule_id'=>1]);
//            ->one();

        //转为原生sql
        var_dump($query->createCommand()->getRawSql());
    }
}
