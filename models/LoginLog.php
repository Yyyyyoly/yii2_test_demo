<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "login_log".
 *
 * @property int $id
 * @property string $login_time
 * @property string $ip
 * @property string $username
 * @property string $area
 * @property string $browser
 */
class LoginLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'login_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['login_time', 'ip', 'username', 'area', 'browser'], 'required'],
            [['login_time'], 'safe'],
            [['ip', 'username', 'area', 'browser'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'login_time' => '登录时间',
            'ip' => '登录ip',
            'username' => '用户账号',
            'area' => '登录地区',
            'browser' => '浏览器名称',
        ];
    }
}
