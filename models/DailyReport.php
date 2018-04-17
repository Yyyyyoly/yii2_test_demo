<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "daily_report".
 *
 * @property int $id
 * @property string $username
 * @property string $time
 * @property int $intention
 * @property int $actual_arrive
 *
 * @property User $username0
 */
class DailyReport extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'daily_report';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'time', 'intention', 'actual_arrive'], 'required'],
            [['time'], 'safe'],
            [['intention', 'actual_arrive'], 'integer'],
            [['username'], 'string', 'max' => 256],
            [['username'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['username' => 'username']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'username' => '用户名称',
            'time' => '时间',
            'intention' => '意向数',
            'actual_arrive' => '实际到访数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserName()
    {
        return $this->hasOne(User::className(), ['username' => 'username']);
    }


    /**
     * 根据用户名称查询日报记录
     * @param $username
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getDailyReportByUserName($username){
        return self::find()
            ->where(['username' => $username])
            ->orderBy('id')
            ->all();
    }

    public function getTime(){
        return date('Y-m-d',strtotime($this->time));
    }
}
