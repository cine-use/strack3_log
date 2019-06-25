<?php
/**
 * Created by PhpStorm.
 * User: weijer
 * Date: 2018/10/29
 * Time: 14:07
 */

namespace app\Clients;


class MessageClient
{
    /**
     * 客户端连接
     * @var \app\Clients\WebSocketClient
     */
    protected $client = null;

    // 连接端口
    protected $port = 0;

    public function __construct()
    {
        //连接 websocket 服务端
        if (!$this->client) {
            $this->connect();
        }
    }

    /**
     * 获取ws连接端口号
     * @return int
     */
    protected function getWsPortConfig()
    {
        if($this->port === 0){
            $ports = get_instance()->config["ports"];
            foreach ($ports as $item) {
                if ($item['socket_type'] == 11) {
                    $this->port = $item['socket_port'];
                }
            }
        }
        return $this->port;
    }

    /**
     * 连接 websocket 服务端
     */
    protected function connect()
    {
        $port = $this->getWsPortConfig();
        $client = new WebSocketClient('127.0.0.1', $port);
        $ret = $client->connect();
        if ($ret) {
            $this->client = $client;
            // 连接成功绑定用户
            $this->sendMessage([
                "method" => "bind",
                "data" => [
                    "uid" => create_uuid(),
                    "group" => "system" // 系统客户端分组
                ]
            ]);
            // 开启心跳检测
            $this->heartbeat();
        }
    }

    /**
     * 失败重连尝试一次
     * @param $data
     */
    protected function sendMessage($data)
    {
        if ($this->client) {
            $message = json_encode($data);
            $this->client->send($message);
        }
    }

    /**
     * 心跳包，半分钟响应一次
     */
    protected function heartbeat()
    {
        if ($this->client) {
            swoole_timer_tick(30000, function () {
                $this->sendMessage(["method" => "heartbeat"]);
            });
        }
    }

    /**
     * 发送消息
     * @param $data
     */
    public function message($data)
    {
        $this->sendMessage($data);
    }
}