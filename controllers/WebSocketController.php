<?php
// note:
// https://github.com/walkor/web-msg-sender
// 使用该功能之前还需要先运行web-socket的服务器，来自workman


namespace app\controllers;

use yii\debug\models\search\Log;
use yii\filters\AccessControl;
use Yii;


class WebSocketController extends \yii\web\Controller
{

    public $counter = 1;

    /**
     * 过滤器  当前socket页面只有已经登录的用户才可以使用
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index','sendMessage'],
                'rules' => [
                    [
                        'actions' => ['index','sendMessage'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }


    /**
     * websocket 监控页
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index',['uid' => Yii::$app->user->id, 'name' => 'webSocket 测试小demo']);
    }


    /**
     * 发送消息
     * @return string
     */
    public function actionSendMessage()
    {
        $uid = Yii::$app->user->id;
        $message = Yii::$app->request->post('message',rand(10,20));
        $num = ++ $this->counter;
        $this->sendMessage($uid, array('msg'=>$message, 'num'=> $num));
    }


    /**
     * 通过推送消息至redis队列中，然后由websocket进程单独处理
     * @param $uids
     * @param $message
     * @return mixed
     */
    public function sendMessage($uids, $message){
        // 指明给谁推送，为空表示向所有在线用户推送
        $to_uid = $uids;

        $post_data = array(
            'type' => 'publish',
            'content' => $message,
            'to' => $to_uid,
        );
       return Yii::$app->redi->lpush('messageList',json_encode($post_data));
    }

}
