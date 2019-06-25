<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 17-9-7
 * Time: 上午10:35
 * 默认控制器：WsController 数据格式：{"controller": "WsController","method": "broadcast"}
 */

namespace app\Controllers;

use app\Models\Service\ValidateService;

class WsController extends BaseController
{
    /**
     * @var \app\Models\Service\ValidateService
     */
    protected $validateService;

    /**
     * @param string $controller_name
     * @param string $method_name
     * @throws \Exception
     */
    protected function initialization($controller_name, $method_name)
    {
        parent::initialization($controller_name, $method_name);
        $this->validateService = $this->loader->model(ValidateService::class, $this);
    }


    /**
     * 验证token数据
     * @return bool
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    protected function checkToken()
    {
        $param = $this->http_input->getAllGet();

        if (strpos($this->request->header["host"], "127.0.0.1") !== false
            || strpos($this->request->header["host"], "localhost") !== false) {
            // 本地服务器不验证token
            return true;
        }

        if (isset($param) && array_key_exists("sign", $param)) {
            $checkResult = $this->validateService->checkToken($param["sign"]);
            if (!$checkResult) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 请求数据格式
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function onConnect()
    {
        if ($this->checkToken()) {
            $this->send(['type' => 'connect', 'status' => 200, "message" => "Connection success."]);
        } else {
            $this->send(['type' => 'connect', 'status' => 404, "message" => "Wrong token."]);
            $this->close();
        }
    }


    /**
     * 添加到分组(不会重复添加, 方法暂时不开放给用户)
     * @param $groupName
     * @throws \Exception
     */
    protected function addToGroup($groupName)
    {
        $this->addSub($groupName);
    }

    /**
     * 绑定UID
     * @throws \Exception
     */
    public function bind()
    {
        $data = $this->client_data->data;

        // 验证参数信息
        if ($this->checkWsRequestParam(["uid", "group"], $data)) {
            // 绑定客户端身份id
            $this->bindUid($data->uid);
            // 把用户添加到分组
            $this->addToGroup($data->group);
            $this->send(['type' => 'bind', 'status' => 200, "message" => "Binding success, uid: {$data->uid}"]);
        } else {
            $this->send(['type' => 'bind', 'status' => 404, "message" => "Parameter is incorrect."]);
            $this->close();
        }
    }


    /**
     * 心跳检测
     * @throws \Exception
     */
    public function heartbeat()
    {
        $this->send(['type' => 'system', "message" => "pong"]);
    }

    /**
     * 广播消息
     * @throws \Server\CoreBase\SwooleException
     * @throws \Exception
     */
    public function broadcast()
    {
        $clientData = $this->client_data;
        // 验证参数信息
        if ($this->checkWsRequestParam(["data"], $clientData)) {
            $this->sendToAll($clientData->data);
        }else {
            $this->send(['type' => 'system', 'status' => 404, "message" => "Parameter is incorrect."]);
        }
    }

    /**
     * 发送消息给指定分组
     * @throws \Server\CoreBase\SwooleException
     * @throws \Exception
     */
    public function sendToGroup()
    {
        $clientData = $this->client_data;
        // 验证参数信息
        if ($this->checkWsRequestParam(["group", "data"], $clientData)) {
            $this->sendPub($clientData->group, $clientData->data);
        }else {
            $this->send(['type' => 'system', 'status' => 404, "message" => "Parameter is incorrect."]);
        }
    }

    /**
     * 发送消息给指定的用户
     * @throws \Server\CoreBase\SwooleException
     * @throws \Exception
     */
    public function sendToUsers()
    {
        $clientData = $this->client_data;
        // 验证参数信息
        if ($this->checkWsRequestParam(["uids", "data"], $clientData)) {
            $this->sendToUids($clientData->uids, $clientData->data);
        }else {
            $this->send(['type' => 'system', 'status' => 404, "message" => "Parameter is incorrect."]);
        }
    }

    /**
     * 关闭连接
     */
    public function onClose()
    {

    }
}
