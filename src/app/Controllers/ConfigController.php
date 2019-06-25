<?php

namespace app\Controllers;

use app\Models\Service\ConfigService;

class ConfigController extends BaseController
{
    /**
     * @var \app\Models\Service\ConfigService
     */
    protected $configService;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->configService = $this->loader->model(ConfigService::class, $this);
    }

    /**
     * 添加配置
     * @throws \Throwable
     */
    public function http_add()
    {
        // 参数获取
        $param = $this->http_input->getAllPost();
        $resData = $this->configService->updateConfig($param);
        $this->response($resData);
    }
}