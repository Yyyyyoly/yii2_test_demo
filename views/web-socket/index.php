<?php
/* @var $this yii\web\View */
use yii\helpers\Html;

$this->title = $name;
?>
<script src='//cdn.bootcss.com/socket.io/1.3.7/socket.io.js'></script>
<script>
    // 初始化io对象
    var socket = io('http://127.0.0.1:3120');
    // uid 可以为网站用户的uid，作为例子这里用session_id代替
    var uid = '<?= $uid?>';
    // 当socket连接后发送登录请求g
    socket.on('connect', function(){
        socket.emit('login', uid);
    });
    // 当服务端推送来消息时触发，这里简单的alert出来，用户可做成自己的展示效果
    socket.on('new_msg', function(msg){
        var decode_msg = JSON.parse(entityToString(msg));
        $('#rtn_msg').text(decode_msg.msg);
        $('#rtn_msg_num').text(decode_msg.num);
    });

    // 替代php htmlspecialchars_decode
    function entityToString(entity){
        var div=document.createElement('div');
        div.innerHTML=entity;
        var res=div.innerText||div.textContent;
        return res;
    }

    function sendMsg(){
        var msg = $("#input").val();
        if(!msg){
            alert('输入不能为空！')
        }
        else{
            $.ajax({
                type  : 'post',
                url   : '/web-socket/send-message',
                dataType:'json',
                data:{'message' :msg },
                success:function() {
                    alert("success");
                }
            });
        }
    }

</script>

<table class="table table-bordered text-center">
    <tbody>
    <tr>
        <th>输入文本<code>url:http://localhost:2120</code></th>
        <th>发送消息</th>
        <th>后台返回推送<code>url:http://localhost:2121</code></th>
    </tr>
    <tr>
        <td>
            <input type="text" class="form-control" id="input" placeholder="Enter ...">
        </td>
        <td>
            <button type="button" class="btn btn-block btn-primary btn-sm" onclick="sendMsg()">发送</button>
        </td>
        <td>
            <i class="fa fa-inbox"></i> 消息信箱
            <span class="label label-primary pull-right" id="rtn_msg"></span> 请求次数
            <span class="label label-primary pull-right" id="rtn_msg_num"></span>
        </td>
    </tr>
    </tbody>
</table>
