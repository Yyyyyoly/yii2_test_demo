<?php
// note:
// https://github.com/walkor/web-msg-sender


namespace app\controllers;

use yii\filters\AccessControl;
use Yii;


class WebSocketController extends \yii\web\Controller
{

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
        $num = $GLOBALS['count']++;
//        $this->sendMessage($uid, array('msg'=>$message, 'num'=> $num));
        $this->sendMessageTwo($uid, array('msg'=>$message, 'num'=> $num));
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

        $post_data =  array(
            'type' => 'publish',
            'content' => $message,
            'to' => $to_uid,
        );
       return Yii::$app->redis->lpush('messageList',json_encode($post_data));
    }


    /**
     * 推送消息至中转http服务器中
     * @param $uids
     * @param $message
     * @return mixed
     */
    public function sendMessageTwo($uids, $message){
        // 指明给谁推送，为空表示向所有在线用户推送
        $to_uid = $uids;

        $post_data = array(
            'type' => 'publish',
            'content' => json_encode($message),
            'to' => $to_uid,
        );

        // 推送的url地址，即socket服务器workStart后监听的中转服务器地址
        $push_api_url = "http://127.0.0.1:3121/";

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        return $return;
    }

}
