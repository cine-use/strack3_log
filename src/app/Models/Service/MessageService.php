<?php

namespace app\Models\Service;

use app\Models\Dao\MessageDao;
use Server\Memory\Pool;

class MessageService extends BaseService
{

    /**
     * @var \app\Models\Dao\MessageDao
     */
    protected $messageDao;

    /**
     * @var \app\Models\Service\MessageMemberService
     */
    protected $messageMemberService;

    /**
     * @var \app\Controllers\EmailController
     */
    protected $emailController;
    //发送消息语言包
    protected $enLang = [
        "update_message_title" => "Modification  Notification!",
        "delete_message_title" => "Delete Notification!",
        "add_message_title" => "Create Notification!"
    ];
    //中文包
    protected $zhLang = [
        "update_message_title" => "修改通知",
        "delete_message_title" => "删除通知",
        "add_message_title" => "创建通知",
        "add" => "添加",
        "update" => "修改",
        "delete" => "删除",
    ];

    /**
     * @param $context
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(&$context)
    {
        parent::initialization($context);

        $this->messageDao = $this->loader->model(MessageDao::class, $this);
        $this->messageMemberService = $this->loader->model(MessageMemberService::class, $this);
        $this->emailController = Pool::getInstance()->get("\\app\\Controllers\\EmailController");
    }

    /**
     * 查询多条 Message
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function selectMessage($param)
    {
        // 获取属于我的message
        $messageMemberData = $this->messageMemberService->selectMessageMember($param);
        $messageIds = array_column($messageMemberData["rows"], "message_id");

        if (!empty($messageIds)) {
            // 获取messageMember数据
            $param["filter"]["message"]["id"] = ["IN", join(",", $messageIds)];

            $param["order"] = ["message.created" => "desc"];

            // 获取message数据
            $messageData = $this->messageDao->select($param);
        } else {
            $messageData = [
                "total" => 0,
                "rows" => []
            ];
        }

        return $messageData;
    }

    /**
     * 添加数据
     * @param $data
     * @return array
     * @throws \Throwable
     */
    public function add($data)
    {
        $resData = $this->messageDao->add($data);
        return $this->response($this->messageDao, $resData);
    }

    /**
     * 保存数据
     * @param $data
     * @return mixed
     * @throws \Throwable
     */
    public function saveMessage($data)
    {
        // 消息数据
        $messageData = $data['message_data']['message'];
        $messageData['identity_id'] = $messageData['identity_id']['identity_id'];
        $primaryIds = $messageData['primary_id'];

        // 存放消息最后添加完成的ID 格式：primary_id=>insert_id
        $lastMessageIds = [];
        // 保存消息
        if (strpos($messageData["primary_id"], ",") === false) {
            $result = $this->messageDao->add($messageData);
            $lastMessageIds[$messageData["primary_id"]] = $result["insert_id"];
        } else {
            $messageIds = explode(",", $messageData["primary_id"]);
            foreach ($messageIds as $messageItem) {
                $messageData["primary_id"] = $messageItem;
                $result = $this->messageDao->add($messageData);
                $lastMessageIds[$messageItem] = $result["insert_id"];
            }
        }
        // 保存成员数据
        $memberData = $data['message_data']['member'];
        $userData = $this->messageMemberService->saveMessageMember($memberData, $lastMessageIds, $primaryIds, $messageData['created_by']);

        $template = "item";
        $emailData = $this->dealEmailContent($template, $data, $userData["email"]);
        $emailData = $this->emailController->initParam($emailData);
        //wechat参数
        $emailData["param"]["wechat"] = join(",", $userData["wechat"]);
        $emailData["param"]["detail_url"] = $data["response_data"]["detail_url"];
        $this->emailController->addToQueue($emailData);
        Pool::getInstance()->push($this->emailController);
        return $emailData;
    }

    /**
     * 处理邮件发送内容
     * @param $emailTemplate
     * @param $data
     * @param $userEmail
     * @return array
     */
    public function dealEmailContent($emailTemplate, $data, $userEmail)
    {
        $responseData = $data["response_data"];
        //语言包
        $language = $responseData["message"]["language"];
        //什么操作
        $operate = $responseData["message"]["operate"];
        //操作时间
        $operationTime = date("Y-m-d H:i:s", $responseData["created"]);
        //操作者
        $operationOf = $responseData["message"]["title"]["created_by"];
        //通用参数
        $operationRelatedData = [
            "operate" => $operate,
            "time" => $operationTime,
            "operator" => $operationOf,
            "email_list" => $userEmail,
            "item_name" => $responseData["message"]["title"]["item_name"]
        ];
        switch ($emailTemplate) {
            case "item":
                $emailData = $this->generateTemplateItem($responseData, $operationRelatedData, $language);
                break;
            case "ping":
                $emailData = [];
                break;
            case "progress":
                $emailData = [];
                break;
            default:
                $emailData = [];
                break;
        }

        return $emailData;
    }


    /**
     * 获取未读消息数据
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function getUnReadData($param)
    {
        // 获取属于我的message
        $messageMemberData = $this->messageMemberService->selectMessageMember($param);
        $messageIds = array_column($messageMemberData["rows"], "message_id");

        if (!empty($messageIds)) {
            // 获取messageMember数据
            $param["filter"]["message"]["id"] = ["IN", join(",", $messageIds)];

            // 获取message数据
            $messageData = $this->messageDao->select($param);
        } else {
            $messageData = [
                "total" => 0,
                "rows" => []
            ];
        }
        return $messageData;
    }

    /**
     * 获取未读消息条数
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function getUnReadNumber($param)
    {
        // 获取属于我的message
        $messageMemberData = $this->messageMemberService->selectMessageMember($param);
        $messageIds = array_column($messageMemberData["rows"], "message_id");

        if (!empty($messageIds)) {
            // 获取messageMember数据
            $param["filter"]["message"]["id"] = ["IN", join(",", $messageIds)];

            // 获取message数据
            $total = $this->messageDao->getTotalNumber($param);

            // 获取最新一条消息时间
            $param["fields"] = ["message" => ["created"]];
            $param["order"] = ["created", "desc"];

            $lastMessageData = $this->messageDao->find($param);

            return ["massage_number" => $total, "last_message_data" => $lastMessageData];
        } else {
            return ["massage_number" => 0, "last_message_data" => []];
        }
    }

    /**
     * 生成Item模板信息
     * @param $responseData
     * @param $operationData
     * @param string $language
     * @return array
     */
    protected function generateTemplateItem($responseData, $operationData, $language)
    {
        switch ($language) {
            case 'en-us':
                //标题
                $subject = ucfirst($responseData["message"]["title"]["module_name"]) . "  " . $responseData["message"]["title"]["item_name"] . " " . $this->enLang[$operationData["operate"] . "_message_title"];
                //消息标题
                $messageTitle = "Hello, strack user";
                //消息内容
                $messageContent = "The  " . $responseData["module_data"]["code"] . " information name is " . $operationData["item_name"] . " and was " . $operationData["operate"] . " by " . $operationData["operator"] . "  at  " . $operationData["time"] . " . please pay attention . ";
                break;
            case 'zh-cn';
                //标题
                $subject = $responseData["message"]["title"]["module_name"] . "  " . $operationData["item_name"] . " " . $this->zhLang[$operationData["operate"] . "_message_title"];
                //消息标题
                $messageTitle = "你好，Strack用户！";
                //消息内容
                $messageContent = $responseData["message"]["title"]["module_name"] . " " . $operationData["item_name"] . "  信息在 " . $operationData["time"] . " 被 " . $operationData["operator"] . " " . $this->zhLang[$operationData["operate"]] . "。详情如下";
                break;

        }
        //卡片信息
        $cardData = $responseData["message"]["update_list"];
        $baseData = [];
        //格式化操作参数
        foreach ($cardData as $key => $value) {
            if (is_array($value["value"])) {
                continue;
            }
            $baseData[$key]["title"] = $value["lang"];
            $baseData[$key]["detail"] = $value["value"];
        }
        // 发送邮件
        $emailData = [
            "param" => [
                "addressee" => implode(",", $operationData["email_list"]),
                "subject" => $subject
            ],
            "data" => [
                "template" => "item",
                "content" => [
                    "header" => [
                        "title" => $subject
                    ],
                    "body" => [
                        "text" => [
                            "message" => [
                                "title" => $messageTitle,
                                "details" => [
                                    "type" => "text",
                                    "content" => $messageContent
                                ],
                            ]],
                        "card" => [
                            "base" => [
                                [
                                    "name" => $responseData["message"]["title"]["item_name"],
                                    "url" => $responseData["detail_url"],
                                    "item" => $baseData
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $emailData;
    }

}