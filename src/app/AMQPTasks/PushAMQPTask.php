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
use app\Clients\PushClient;


class PushAMQPTask extends AMQPTask
{

    /**
     * @var \app\Clients\PushClient
     */
    public $emailClient;

    /**
     * @param AMQPMessage $message
     * @return \Generator|void
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(AMQPMessage $message)
    {
        parent::initialization($message);
        $this->emailClient = Pool::getInstance()->get(PushClient::class);
    }

    /**
     * 获取队列信息
     * @param $body
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \Server\CoreBase\SwooleException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function handle($body)
    {
        $param = json_decode($body, true);
        $this->send($param);
        $this->ack();
    }

    /**
     * 发送邮件
     * @param $param
     * @throws \Server\CoreBase\SwooleException
     */
    public function send($param)
    {
        $this->emailClient->send($param);
        Pool::getInstance()->push($this->emailClient);
    }
}
