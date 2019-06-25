<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 17-9-15
 * Time: 下午2:28
 */

namespace app\Process;

use app\AMQPTasks\PushAMQPTask;
use Server\Components\AMQPTaskSystem\AMQPTaskProcess;

class PushAmqpProcess extends AMQPTaskProcess
{

    /**
     * 路由消息返回class名称
     * @param $body
     * @return string
     */
    protected function route($body)
    {
        return PushAMQPTask::class;
    }

    /**
     * 开始进程
     * @param $process
     * @throws \Exception
     */
    public function start($process)
    {
        parent::start($process);

        //获取一个channel
        $channel = $this->connection->channel();

        //创建一个队列
        $channel->queue_declare("email");

        //框架默认提供的路由，也可以自己写
        $this->createDirectConsume($channel,'email', 2, false, "email_exchange");

        //等待所有的channel
        $this->connection->waitAllChannel();
    }


    /**
     * 进程关闭回调方法
     */
    protected function onShutDown()
    {

    }
}
