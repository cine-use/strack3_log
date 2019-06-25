<?php

namespace app\Clients;

use PHPMailer\PHPMailer\PHPMailer;

class PushClient
{

    /**
     * PHPMailer对象
     * @var
     */
    protected $phpMailer;
    /**
     * 默认SMTP
     * @var array
     */
    protected $SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    /**
     * 邮件html内容
     * @var
     */
    protected $mailContent;
    /**
     * 调试开关
     * @var bool
     */
    public $debug = false;

    /**
     * 设置收件人地址
     * @param $addressee
     */
    protected function setAddressee($addressee)
    {
        if (strstr($addressee, ",")) {
            $emailList = explode(",", $addressee);
        } else {
            $emailList = [$addressee];
        }
        //设置发件人
        foreach ($emailList as $email) {
            $this->phpMailer->AddAddress($email);
        }
    }


    /**
     * 邮件内容处理
     * @param $param
     */
    protected function setMailContent($param)
    {
        if ($param["template"] == "text") {
            $mailContent = $param["content"];
        } else {
            $this->phpMailer->isHTML(true);
            $filePath = $param["template_path"];
            $mailContent = file_get_contents($filePath);
            unlink($filePath);
        }
        //设置邮件正文
        $this->phpMailer->Body = $mailContent;
        $this->mailContent = $mailContent;
    }


    /**
     * 设置邮件配置
     * @param $param
     */
    protected function setConfig($param)
    {
        $mailConfig = $param["config"];
        //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置为 UTF-8
        $this->phpMailer->CharSet = $mailConfig['charset'];
        //设定使用SMTP服务
        $this->phpMailer->IsSMTP();
        // SMTP 服务器
        $this->phpMailer->Host = $mailConfig["server"];
        // SMTP服务器的端口号
        $this->phpMailer->Port = $mailConfig["port"];
        // SMTP服务器用户名
        $this->phpMailer->Username = $mailConfig["username"];
        // SMTP服务器密码
        $this->phpMailer->Password = $mailConfig["password"];
        // 设置发件人地址和名称
        $this->phpMailer->SetFrom($mailConfig["username"], $mailConfig["addresser_name"]);
        $this->phpMailer->SMTPAuth = true;
        /**
         * 判断端口
         * 25：不用加密 SMTPAutoTLS：false
         * 465：ssl加密 SMTPAutoTLS：true
         * 587：tls加密 SMTPAutoTLS：true
         */
        switch ($mailConfig['port']) {
            case 465:
            case 994:
                $this->phpMailer->SMTPSecure = 'ssl';
                $this->phpMailer->SMTPAutoTLS = true;
                $this->phpMailer->SMTPKeepAlive = true;
                $this->phpMailer->SMTPOptions = $this->SMTPOptions;
                break;
            case 587:
                $this->phpMailer->SMTPSecure = 'tls';
                $this->phpMailer->SMTPAutoTLS = true;
                $this->phpMailer->SMTPKeepAlive = true;
                $this->phpMailer->SMTPOptions = $this->SMTPOptions;
                break;
            case 25:
                $this->phpMailer->SMTPAutoTLS = false;
                break;
            default:
                $this->phpMailer->SMTPSecure = '';
                break;
        }
        //根据open_security 设置安全协议
        $this->phpMailer->SMTPSecure = $mailConfig["open_security"] == 1 ? $mailConfig["smtp_secure"] : false;
        //设置邮件标题
        if (!empty($param["param"]["subject"])) {
            $this->phpMailer->Subject = $param["param"]["subject"];
        }
    }

    /**
     * 发送邮件
     * @param $param
     * @return array
     */
    public function send($param)
    {
        try {
            $this->phpMailer = new PHPMailer();
            //mail debug
            $this->phpMailer->SMTPDebug = $this->debug;
            $this->setConfig($param);
            $this->setAddressee($param["param"]["addressee"]);
            $this->setMailContent($param["data"]);

            if (!$this->phpMailer->send()) {
                $resData = [
                    "status" => 404,
                    "message" => $this->phpMailer->ErrorInfo,
                ];
            } else {
                $resData = [
                    "status" => 200,
                    "message" => "Email successfully sent",
                ];
            }

            // 发送邮件模板微信
            if (array_key_exists("wechat", $param["param"]) && !empty($param["param"]["wechat"])) {
                $WeChatData = $this->setWeChatContent($param);
                $this->sendToWeChat($WeChatData);
            }
        } catch (\Exception $e) {
            $resData = [
                "status" => 404,
                "message" => $e->getMessage(),
            ];

        }
        return $resData;
    }

    /**
     * 设置邮件内容
     * @param $param
     * @return array
     */
    protected function setWeChatContent($param)
    {
        $weChatList = explode(",", $param["param"]["wechat"]);
        $subject = isset($param["param"]["subject"]) ? $param["param"]["subject"] : "";
        $detailUrl = isset($param["param"]["detail_url"]) ? $param["param"]["detail_url"] : "";
        $description = isset($param["data"]["content"]["body"]["text"]["message"]["details"]["content"]) ? $param["data"]["content"]["body"]["text"]["message"]["details"]["content"] : "";
        $data = [
            'touser' => $weChatList,
            'articles' => [
                [
                    'title' => $subject,
                    'detail_url' => $detailUrl,
                    'content' => $this->mailContent,
                    'description' => $description
                ]
            ]
        ];
        return $data;
    }

    /**
     * 发送到企业微信消息
     * @param $data
     * @return array|bool|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    private function sendToWeChat($data)
    {
        $weChatClient = new WechatClient();
        $weChatClient->send($data);
    }
}