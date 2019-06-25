<?php

namespace app\Controllers;

use app\Models\Service\ValidateService;

class KeyPairController extends BaseController
{

    /**
     * 事件模型
     * @var \app\Models\Service\ValidateService
     */
    protected $validateService;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->validateService = $this->loader->model(ValidateService::class, $this);
    }

    /**
     * 显示KeyPair生成获取页面
     */
    public function http_view()
    {
        $template = $this->loader->view('app::key_pair');
        $this->http_output->end($template);
    }

    /**
     * 生成密钥对
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_generate()
    {
        $param = $this->http_input->getAllPost();
        if (array_key_exists("token", $param)) {
            if ($param["token"] === "1e9c9bc238b58038354cc15e0dd2f39f") {
                $keyPairData = $this->validateService->generate();
                $this->response($keyPairData);
            } else {
                $this->response([], 404, "Illegal operation.");
            }
        } else {
            $this->response([], 404, "Illegal operation.");
        }
    }

    /**
     * 验证密码（默认密码为：1e9c9bc238b58038354cc15e0dd2f39f strack@media）
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_verify()
    {
        $param = $this->http_input->getAllPost();
        if (array_key_exists("password", $param)) {
            if (md5($param["password"]) === "1e9c9bc238b58038354cc15e0dd2f39f") {
                //正确密码，返回密钥对配置
                $keyPairData = $this->validateService->getKeyPairData();

                if (empty($keyPairData)) {
                    $keyPairData = [
                        "access_key" => "",
                        "secret_key" => ""
                    ];
                }

                $keyPairData["token"] = "1e9c9bc238b58038354cc15e0dd2f39f";

                $this->response($keyPairData, 200, "Correct password.");

            } else {
                $this->response([], 404, "Wrong password.");
            }
        } else {
            $this->response([], 404, "Illegal operation.");
        }
    }
}