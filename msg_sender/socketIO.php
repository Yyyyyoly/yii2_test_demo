<?php
/**
 * websocket 单独脚本  这个脚本有问题，我的假设不错，但是执行不下去
 * Created by PhpStorm.
 * User: wangyg
 * Date: 2018/4/4
 * Time: 17:23
 */
require_once __DIR__ . '/../vendor/autoload.php';
$redisConfig = require __DIR__ . '/../config/redis.php';

use Workerman\Worker;
use PHPSocketIO\SocketIO;

// 建立redis连接  读取消息队列
$redis = new Redis();
$redis->connect($redisConfig['hostname'], $redisConfig['port']);
$redis->auth($redisConfig['password']);
$redis->select($redisConfig['database']);

// 全局数组保存uid在线数据
$uidConnectionMap = array();

// PHPSocketIO服务
$sender_io = new SocketIO(3120);

// 客户端发起连接事件时，设置连接socket的各种事件回调
$sender_io->on('connection', function($socket)use($sender_io){
    echo "new connection coming\n";
    // 当客户端发来登录事件时触发
    $socket->on('login', function ($uid)use($socket){
        global $uidConnectionMap;
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

/******************************************************************************/
// 以下为失败的尝试：demo中给的workStart后回调是——开启一个新的worker进程监听2021端口，然后有新的message到达就推下去
// 这样意味着我在业务系统中先把消息带到2021的http服务器，然后这个服务器再通过socket的2020端口推送消息至客户端
// 我觉得太麻烦了，要额外来一个http2021服务器，
// 所以尝试直接让workStart后死循环阻塞读取消息队列中的数据，这样一旦有消息就可以直接通过socket2020推下去
// 但是调试过程中发现$sender_io->on('connection',function(){})直接不执行了
// 追究原因——“phpsocket.io提供了workerStart事件回调，也就是当进程启动后准备好接受客户端链接时触发的回调。 一个进程生命周期只会触发一次”
// 因为这里死循环了。。。。所以回调一直没有结束，而php是同步非异步处理的，所以进程后续无法处理connection事件
/******************************************************************************/

// 当$sender_io启动后监听redis消息队列  如果队列中有数据，就处理同时下发消息
$sender_io->on('workerStart', function(){
    global $redis;
    while(true){
        $newMessageArray = $redis->brpop('messageList',10);
        $newMessage = count($newMessageArray) === 0 ? '' : $newMessageArray[1];
        if($newMessage){
            $commands = json_decode($newMessage,true);
            switch($commands['type']) {
                case 'publish':
                    global $sender_io;
                    $to = $commands['to'];
                    $content = json_encode($commands['content']);
                    $content = htmlspecialchars($content);
                    // 有指定uid则向uid所在socket组发送数据
                    if ($to) {
                        $sender_io->to($to)->emit('new_msg', $content);
                    } else {
                        // 否则向所有uid推送数据
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
