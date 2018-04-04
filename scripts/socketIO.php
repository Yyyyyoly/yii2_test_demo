<?php
/**
 * websocket 单独脚本
 * Created by PhpStorm.
 * User: wangyg
 * Date: 2018/4/4
 * Time: 17:23
 */

use Yii;
use WorkermanForWindows\Worker;
use PHPSocketIOForWindows\SocketIO;

// 全局数组保存uid在线数据
$uidConnectionMap = array();
// 记录最后一次广播的在线用户数
$last_online_count = 0;
// 记录最后一次广播的在线页面数
$last_online_page_count = 0;

// PHPSocketIO服务
$sender_io = new SocketIO(2120);
// 客户端发起连接事件时，设置连接socket的各种事件回调
$sender_io->on('connection', function($socket){
    // 当客户端发来登录事件时触发
    $socket->on('login', function ($uid)use($socket){
        global $uidConnectionMap, $last_online_count, $last_online_page_count;
        // 已经登录过了
        if(isset($socket->uid)){
            return;
        }
        // 更新对应uid的在线数据
        $uid = (string)$uid;
        if(!isset($uidConnectionMap[$uid]))
        {
            $uidConnectionMap[$uid] = 0;
        }
        // 这个uid有++$uidConnectionMap[$uid]个socket连接
        ++$uidConnectionMap[$uid];
        // 将这个连接加入到uid分组，方便针对uid推送数据
        $socket->join($uid);
        $socket->uid = $uid;
        // 更新这个socket对应页面的在线数据
        $socket->emit('update_online_count', "当前<b>{$last_online_count}</b>人在线，共打开<b>{$last_online_page_count}</b>个页面");
    });

    // 当客户端断开连接是触发（一般是关闭网页或者跳转刷新导致）
    $socket->on('disconnect', function () use($socket) {
        if(!isset($socket->uid))
        {
            return;
        }
        global $uidConnectionMap;
        // 将uid的在线socket数减一
        if(--$uidConnectionMap[$socket->uid] <= 0)
        {
            unset($uidConnectionMap[$socket->uid]);
        }
    });
});

// 当$sender_io启动后监听redis消息队列  如果队列中有数据，就处理同时下发消息
$sender_io->on('workerStart', function(){
    while(true){
        $newMessage = Yii::$app->redis->brpop('messageList');
        if($newMessage){
            $commands = json_decode($newMessage,true);
            switch($commands['type']) {
                case 'publish':
                    global $sender_io;
                    $to = $commands['to'];
                    $content = htmlspecialchars($commands['content']);
                    // 有指定uid则向uid所在socket组发送数据
                    if ($to) {
                        $sender_io->to($to)->emit('new_msg', $content);
                        // 否则向所有uid推送数据
                    } else {
                        $sender_io->emit('new_msg', $content);
                    }
            }
        }
    }
});

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
