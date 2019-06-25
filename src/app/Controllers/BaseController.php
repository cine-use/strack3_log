<?php

namespace app\Controllers;

use Server\CoreBase\Controller;
use app\Models\Service\ValidateService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BaseController extends Controller
{

    // 交换机名称
    protected $exchange = "exchange_name";

    //队列名称
    protected $queue = 'queue_name';


    //错误信息
    protected $errorMsg = "";

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->errorMsg;
    }

    /**
     * 发起 AMQP 连接
     */
    protected function connectAMQP()
    {
        $active = $this->config['amqp']['active'];
        $host = $this->config['amqp'][$active]['host'];
        $port = $this->config['amqp'][$active]['port'];
        $user = $this->config['amqp'][$active]['user'];
        $password = $this->config['amqp'][$active]['password'];
        $vhost = $this->config['amqp'][$active]['vhost'];

        $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $channel = $connection->channel();
        $channel->exchange_declare($this->exchange, 'direct');
        $channel->queue_bind($this->queue, $this->exchange);
        $channel->queue_declare($this->queue);
        return $channel;
    }


    /**
     * 添加进入消息队列
     * @param $param
     */
    protected function publisher($param)
    {
        $channel = $this->connectAMQP();
        $message = new AMQPMessage(json_encode($param));
        $channel->basic_publish($message, $this->exchange);
    }

    /**
     * 生成返回数据
     * @param $data
     * @param int $code
     * @param string $msg
     * @return array
     */
    protected function generateResponseData($data, $code = 200, $msg = '')
    {
        return ["status" => $code, "message" => $msg, "data" => $data];
    }

    /**
     * 通用返回数据
     * @param $data
     * @param int $code
     * @param string $msg
     */
    protected function response($data, $code = 200, $msg = '')
    {
        $resData = $this->generateResponseData($data, $code, $msg);
        $jsonString = json_encode($resData);
        if (mb_strlen($jsonString) > 1000000) {
            // 大数据分片返回
            $jsonStringArr = str_split($jsonString, 1000000);
            foreach ($jsonStringArr as $blockString) {
                $this->http_output->response->write($blockString);
            }
            $this->http_output->response->end();
        } else {
            $this->http_output->end($jsonString);
        }
    }


    /**
     * 扩展http input 获取 Row Post值
     */
    protected function getPostRowData()
    {
        $swoole_http_request = $this->http_input->request;
        if (array_key_exists('content-type', $swoole_http_request->header) && false !== strpos($swoole_http_request->header['content-type'], 'application/json')) {
            $this->http_input->request->post = json_decode($swoole_http_request->rawcontent(), true);
        }
    }

    /**
     * 处理过滤条件中的方法名
     * @param $data
     * @return mixed
     */
    protected function parserFilter(&$data)
    {
        if (array_key_exists("filter", $data)) {
            array_walk_recursive($data, [$this, 'parserFilterCondition']);
        }
        return $data;
    }

    /**
     * 替换过滤条件中的方法名
     * @param $val
     */
    protected function parserFilterCondition(&$val)
    {
        $map = [
            "-eq" => "=", // 等于
            "-in" => "IN",// 在里面
            "-neq" => "<>",// 不等于
            "-lk" => "LIKE",// 模糊查询（像）
            "-not-lk" => "NOT LIKE", // 模糊查询（不像）
            "-gt" => ">",// 大于
            "-egt" => ">=",// 大于等于
            "-lt" => "<", // 小于
            "-elt" => "<=",// 小于等于
            "-bw" => "BETWEEN",// 在之间
            "-not-bw" => "NOT BETWEEN",// 不在之间
            "-not-in" => "NOT IN",// 不在里面
        ];
        if (array_key_exists($val, $map)) {
            $val = $map[$val];
        }
    }

    /**
     * 检查请求参数
     * @param $param
     * @return mixed
     */
    protected function checkRequestParam($param)
    {
        if (empty($param)) {
            $this->response("", 404, "request param not null");
        }
        return $this->parserFilter($param);
    }

    /**
     * 检测WebSocket请求参数
     * @param $keys
     * @param $param
     * @return bool
     */
    protected function checkWsRequestParam($keys, $param)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $param) && !isset($param->{$key})) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断是否是合法请求
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    protected function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);

        $controllerNameLower = strtolower($this->context["controller_name"]);

        // http请求处理
        if ($this->request_type === "http_request") {

            // 如果是json格式进一步处理
            $this->getPostRowData();

            // 这里判断http请求授权权限
            if ($controllerNameLower !== "keypaircontroller") {
                // 除了Token页面外都需要验证token是否有效
                $param = $this->http_input->getAllGet();
                if (!empty($param["sign"])) {
                    $validateService = $this->loader->model(ValidateService::class, $this);
                    $checkResult = $validateService->checkToken($param["sign"]);
                    if (!$checkResult) {
                        $this->response([], 404, "Wrong token.");
                    }
                } else {
                    $this->response([], 404, "Token does not exist.");
                }
            }
        }
    }
}