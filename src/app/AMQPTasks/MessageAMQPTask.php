<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 17-9-7
 * Time: 上午10:35
 */

namespace app\AMQPTasks;

use PhpAmqpLib\Message\AMQPMessage;
use Server\Components\AMQPTaskSystem\AMQPTask;
use Server\Memory\Pool;
use app\Clients\MessageClient;

class MessageAMQPTask extends AMQPTask
{

    /**
     * @var \app\Clients\MessageClient
     */
    public $messageClient;

    /**
     * @param AMQPMessage $message
     * @return \Generator|void
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(AMQPMessage $message)
    {
        parent::initialization($message);
        $this->messageClient = Pool::getInstance()->get(MessageClient::class);
    }

    /**
     * 获取队列信息
     * @param $body
     * @throws \Server\CoreBase\SwooleException
     */
    public function handle($body)
    {
        $this->messageClient->message(json_decode($body, true));
        Pool::getInstance()->push($this->messageClient);
        $this->ack();
    }
}
