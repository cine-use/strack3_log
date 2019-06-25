<?php

namespace app\Controllers;

use Server\Memory\Pool;
use app\Models\Service\ConfigService;

class EmailController extends BaseController
{
    /**
     * @var \app\Models\Service\ConfigService
     */
    protected $configService;

    // 交换机名称
    protected $exchange = "email_exchange";

    //队列名称
    protected $queue = 'email';

    //邮件模板路径
    protected $mailTemplatePath = MAIL_DIR . "/Template/";

    //邮件模板
    protected $templateList = ["item", "ping", "progress", "text"];

    /**
     * 邮件发送客户端
     * @var \app\Clients\PushClient
     */
    protected $emailClient;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->emailClient = Pool::getInstance()->get("\\app\\Clients\\PushClient");
    }

    /**
     * 加入发送队列
     * @param $param
     */
    public function addToQueue($param)
    {
        //加入指定到AMQP队列
        $this->publisher($param);
    }

    /**
     * 初始化邮件配置并检查邮件参数
     * @param $param
     * @return bool
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function initParam($param)
    {
        $this->configService = $this->loader->model(ConfigService::class, $this);
        $emailConfig = $this->configService->getConfig("email_settings");
        if (empty($emailConfig)) {
            $this->errorMsg = "Mail Config No Set";
            return false;
        }
        $param["config"] = $emailConfig;
        //检查邮件参数
        if (!$this->checkEmailParam($param)) {
            return false;
        }
        //生成html
        if ($param["data"]["template"] != "text") {
            $templatePath = $this->generateTemplateFile($param["data"]["template"], $param["data"]["content"]);
            if ($templatePath) {
                $param["data"]["template_path"] = $templatePath;
            } else {
                return false;
            }
        }
        return $param;
    }

    /**
     * 检查邮件发送参数
     * @param $param
     * @return bool
     */
    protected function checkEmailParam($param)
    {
        //检查外围参数
        $requirePeripheralParam = ["config", "data", "param"];
        //检查邮件配置
        $requireConfigParam = ["server", "username", "password", "port", "charset", "addresser_name", "smtp_secure", "open_email", "open_security"];
        if (!$this->checkRequireParam($param, $requirePeripheralParam) || !$this->checkRequireParam($param["config"], $requireConfigParam)) {
            return false;
        }
        //检查邮件服务器是否开启
        if ($param["config"]["open_email"] == 0) {
            $this->errorMsg = "Mail service closed";
            return false;
        }
        //检查收件人是否有效
        if (!$this->checkAddressee($param["param"]) || !$this->checkContent($param["data"])) {
            return false;
        }
        return true;
    }

    /**
     * 检查邮件列表
     * @param $param
     * @return bool
     */
    protected function checkAddressee($param)
    {
        $requireParam = ["addressee"];
        if (!$this->checkRequireParam($param, $requireParam)) {
            return false;
        }
        $addressee = $param["addressee"];
        if (strstr($addressee, ",")) {
            $emailList = explode(",", $addressee);
        } else {
            $emailList = [$addressee];
        }
        foreach ($emailList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->errorMsg = "This $email email address is invalid";
                return false;
            }
            $splitEmail = explode("@", $email);
            if (checkdnsrr(array_pop($splitEmail), "MX") === false) {
                $this->errorMsg = $email . "Mail providers do not exist";
                return false;
            }
        }
        return true;
    }

    /**
     * 检查邮件内容
     * @param $param
     * @return bool
     */
    protected function checkContent($param)
    {
        if (!$this->checkRequireParam($param, ["template", "content"])) {
            return false;
        }
        if (in_array($param["template"], $this->templateList)) {
            if ($param["template"] == "text" && !is_string($param["content"])) {
                $this->errorMsg = "check your mail content";
                return false;
            }
        } else {
            $this->errorMsg = "Mail templates do not exist";
            return false;
        }
        return true;
    }

    /**
     * 检查必要字段
     * @param $param
     * @param $requireParam
     * @return bool
     */
    protected function checkRequireParam($param, $requireParam)
    {
        //检查外层参数
        foreach ($requireParam as $val) {
            if (!array_key_exists($val, $param) || empty($param[$val]) && $param[$val] != 0) {
                $this->errorMsg = $val . " " . "require param not exist";
                return false;
            }
        }
        return true;
    }

    /**
     * 生成html
     * @param $template
     * @param $content
     * @return bool|string
     */
    protected function generateTemplateFile($template, $content)
    {
        //cache 目录
        $cacheDir = $this->mailTemplatePath . "Cache/";
        create_directory($cacheDir);
        try {
            $loader = new \Twig_Loader_Filesystem($this->mailTemplatePath);
            $twig = new \Twig_Environment($loader, array());
            //设置邮件页脚脚
            $param["content"]["footer"]["copyright"] = "Copyright © " . "2016-" . date('Y') . " Strack . All rights reserved.";
            //填充模板信息
            $mailContent = $twig->render($template . ".html", $content);
            //保存到缓存目录
            $fileName = "mail" . string_random(6) . ".html";
            $fileName = $cacheDir . $fileName;
            file_put_contents($fileName, $mailContent);
            return $fileName;
        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            return false;
        }

    }

    /**
     * 发送邮件接口
     * @throws \Throwable
     */
    public function http_send()
    {
        $requestParam = $this->http_input->getAllPost();
        $param = $this->initParam($requestParam);
        if (!$param) {
            $this->response(["status" => 404, "message" => $this->getError()]);
        } else {
            $this->addToQueue($param);
            $this->response(["status" => 200, "message" => "Email successfully sent"]);
        }
    }

    /**
     * 发送测试邮件接口
     * @throws \Throwable
     */
    public function http_test()
    {
        $requestParam = $this->http_input->getAllPost();
        $param = $this->initParam($requestParam);

        if (!$param) {
            $this->response(["code" => 404, "message" => $this->getError()]);
        } else {
            $this->emailClient->debug = true;
            $resData = $this->emailClient->send($param);
            Pool::getInstance()->push($this->emailClient);
            $this->response($resData);
        }

    }

    /**
     * 返回邮件模板列表
     */
    public function http_template()
    {
        $this->response($this->templateList);
    }
}