<?php

namespace app\Controllers;

use app\Models\Service\MessageMemberService;
use app\Models\Service\MessageService;

class MessageController extends BaseController
{


    // 交换机名称
    protected $exchange = "message_exchange";

    //队列名称
    protected $queue = 'message';


    /**
     * 事件模型
     * @var \app\Models\Service\MessageService
     */
    protected $messageService;

    /**
     * @var \app\Models\Service\MessageMemberService
     */
    protected $messageMemberService;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->messageService = $this->loader->model(MessageService::class, $this);
        $this->messageMemberService = $this->loader->model(MessageMemberService::class, $this);
    }

    /**
     * 加入消息队列
     * @param $data
     * @param $group
     * @param string $method
     */
    public function addToQueue($data, $group, $method = "broadcast")
    {
        $message = ["method" => $method, "group" => $group, "data" => $data];
        $this->publisher($message);
    }

    /**
     * 添加message
     * @throws \Throwable
     */
    public function http_add()
    {
        // 参数获取
        $param = $this->http_input->getAllPost();
        if (!empty($param["message_data"]["member"])) {
            $resData = $this->messageService->saveMessage($param);
        } else {
            $resData = [];
        }
        // 加入消息队列
        $this->addToQueue($param["response_data"], "message", "sendToGroup");
        $this->response($resData);
    }

    /**
     * select查询
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_select()
    {
        $param = $this->http_input->getAllPost();
        $queryData = $this->checkRequestParam($param);
        $resData = $this->messageService->selectMessage($queryData);
        $this->response($resData);
    }

    /**
     * 获取未读消息数据
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_getUnReadData()
    {
        $param = $this->http_input->getAllPost();
        $queryData = $this->checkRequestParam($param);
        $resData = $this->messageService->getUnReadData($queryData);
        $this->response($resData);
    }


    /**
     * 获取未读消息条数
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_getUnReadNumber()
    {
        $param = $this->http_input->getAllPost();
        $queryData = $this->checkRequestParam($param);
        $resData = $this->messageService->getUnReadNumber($queryData);
        $this->response($resData);
    }


    /**
     * 设置指定的消息为已读
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_read()
    {
        $param = $this->http_input->getAllPost();
        $queryData = $this->checkRequestParam($param);
        $resData = $this->messageMemberService->read($queryData);
        $this->response($resData);
    }
}