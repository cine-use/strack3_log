<?php

namespace app\Controllers;

use Server\Memory\Pool;

class WechatController extends BaseController
{

    /**
     * 微信企业信息
     * @var \app\Clients\WechatClient
     */
    protected $wechatClient;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->wechatClient = Pool::getInstance()->get("\\app\\Clients\\WechatClient");
    }

    /**
     * 测试企业微信消息发送
     * @throws \Server\CoreBase\SwooleException
     */
    public function http_test()
    {
        $this->wechatClient->init();

        $testData = [
            'touser' => ['weijer', 'wwwwwwww'],
            'articles' => [
                [
                    'title' => 'strack test title',
                    'detail_url' => 'http://www.cineuse.com',
                    'content' => '<div style="color:red;width:100%;height:100px;">strack test content</div>',
                    'description' => 'strack test description'
                ]
            ]
        ];

        $resData = $this->wechatClient->send($testData);

        Pool::getInstance()->push($this->wechatClient);
        $this->response($resData);
    }
}