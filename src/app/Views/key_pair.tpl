<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <title>获取或者生成密钥对</title>
    <link rel="stylesheet" href="/static/css/layui.css">
    <link rel="icon" href="/static/images/favicon.ico" />
    <style>
        .form-main{
            width: 520px;
            margin: 0 auto;
            padding: 120px 20px 20px;
        }
        .key-pair-wrap{

        }
        .key-pair-item{
            margin-bottom: 15px;
        }
        .key-pair-name{
            height: 25px;
            line-height: 25px;
            font-weight: 600;
        }
        .key-pair-content{
            min-height: 40px;
            line-height: 22px;
            word-break:break-all;
        }
    </style>
</head>
<body>
<!-- 你的HTML代码 -->
<div class="form-main">
   <div class="key-pair-wrap">
       <div class="key-pair-item">
           <div class="key-pair-name">
                许可码
           </div>
           <div id="access_key_content" class="key-pair-content">

           </div>
       </div>
       <div class="key-pair-item">
           <div class="key-pair-name">
               密钥
           </div>
           <div id="secret_key_content" class="key-pair-content">

           </div>
       </div>
       <div style="margin: 0;">
           <!-- layui 2.2.5 新增 -->
           <button id="generate_key" class="layui-btn layui-btn-fluid">生成密钥对</button>
       </div>
   </div>
</div>

<script src="/static/layui.js"></script>
<script>
    //一般直接写在一个js文件中
    layui.use(['jquery', 'layer', 'form'], function(){
        var $ = layui.$
            ,layer = layui.layer;

        var token = '';

        $('#generate_key').on('click', function(){
            if(token){
                $.ajax({
                    type: 'POST',
                    url: '/KeyPair/generate',
                    dataType: 'json',
                    data: {
                        token: token
                    },
                    success: function (data) {
                        if(parseInt(data["status"]) === 200){
                            // 密码正确
                            $("#access_key_content").html(data["data"]["access_key"]);
                            $("#secret_key_content").html(data["data"]["secret_key"]);
                        }else {
                            layer.msg(data["message"]);
                        }
                    }
                });
            }
        });

        //prompt层
        layer.open({
            type: 1,
            title :'请输入密码',
            skin: 'layui-layer-prompt', //样式类名
            closeBtn: 0, //不显示关闭按钮
            btn: ['确定'],
            shade: [1,'#fff'],
            content: '<input id="pass" type="password" class="layui-layer-input" value="">',
            yes: function(index){
                var value = $("#pass").val();
                if(value.length === 0){
                    layer.msg("请输入密码！");
                }else {
                    $.ajax({
                        type: 'POST',
                        url: '/keypair/verify',
                        data: {
                            password: value
                        },
                        dataType: 'json',
                        success: function (data) {
                            if(parseInt(data["status"]) === 200){
                                // 密码正确
                                token = data["data"]["token"];
                                $("#access_key_content").html(data["data"]["access_key"]);
                                $("#secret_key_content").html(data["data"]["secret_key"]);
                                layer.close(index);
                            }else {
                                layer.msg(data["message"]);
                            }
                        }
                    });
                }
            }
        });
    });
</script>
</body>
</html>