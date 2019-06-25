<?php

namespace app\Controllers;

use Server\Memory\Pool;
use app\Models\Service\EventService;

class EventController extends BaseController
{
    /**
     * 事件模型
     * @var \app\Models\Service\EventService
     */
    protected $eventService;

    /**
     * @var \app\Controllers\MessageController
     */
    protected $messageController;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->eventService = $this->loader->model(EventService::class, $this);
    }

    /**
     * 把event加入到消息队列
     * @param $data
     * @throws \Server\CoreBase\SwooleException
     */
    protected function addToMessageQueue($data){
        $this->messageController = Pool::getInstance()->get("\\app\\Controllers\\MessageController");
        $this->messageController->addToQueue($data, "eventlog", "sendToGroup");
        Pool::getInstance()->push($this->messageController);
    }

    /**
     * 返回Event_log表的fields
     */
    public function http_fields()
    {
        $fieldsData = $this->eventService->getFieldsConfig();
        $this->response($fieldsData);
    }

    /**
     * 添加 event
     * @throws \Throwable
     */
    public function http_add()
    {
        $param = $this->http_input->getAllPost();
        $resData = $this->eventService->addEvent($param);
        $this->addToMessageQueue($param);
        $this->response($resData);
    }

    /**
     * 多条查找 event
     * @throws \Throwable
     */
    public function http_select()
    {
        $param = $this->http_input->getAllPost();
        $queryData = $this->checkRequestParam($param);
        $resData = $this->eventService->selectEvent($queryData);
        $this->response($resData);
    }

    /**
     * 单条查找 event
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_find()
    {
        //参数获取
        $param = $this->http_input->getAllPost();
        $queryData = $this->checkRequestParam($param);
        $resData = $this->eventService->findEvent($queryData);
        $this->response($resData);
    }
}