<?php

namespace app\Models\Service;

use app\Models\Dao\MessageMemberDao;

class MessageMemberService extends BaseService
{

    /**
     * @var \app\Models\Dao\MessageMemberDao
     */
    protected $messageMemberDao;

    /**
     * @param $context
     */
    public function initialization(&$context)
    {
        parent::initialization($context);
        $this->messageMemberDao = $this->loader->model(MessageMemberDao::class, $this);
    }

    /**
     * 生成消息成员保存参数
     * @param $param
     * @return array
     */
    private function generateMemberParam($param)
    {
        $memberData = [
            'message_id' => $param['message_id'],
            'status' => 'unread',
            'user_id' => $param['id'],
            'name' => $param['name'],
            'email' => $param['email'],
            'user_uuid' => $param['uuid'],
            'belong_type' => $param['belong_type'],
            'created_by' => $param['created_by'],
            'json' => $param,
        ];

        return $memberData;
    }

    /**
     * 生成成员保存数据
     * @param $data
     * @param $messageIds
     * @param $primaryIds
     * @param $createdBy
     * @return array
     * @throws \Throwable
     */
    public function saveMessageMember($data, $messageIds, $primaryIds, $createdBy)
    {
        $userData = [];
        if (!empty($data)) {
            // 保存成员信息
            $primaryIdData = explode(',', $primaryIds);
            foreach ($primaryIdData as $primaryItem) {
                if (array_key_exists($primaryItem, $data) && !empty($data[$primaryItem])) {
                    foreach ($data[$primaryItem] as &$memberItem) {

                        $memberItem['message_id'] = $messageIds[$primaryItem];
                        $memberItem['created_by'] = $createdBy;

                        // 获取参数并保存数据
                        $saveData = $this->generateMemberParam($memberItem);
                        $this->messageMemberDao->add($saveData);

                        // 将成员信息返回
                        $userData["email"][] = $memberItem['email'];
                        $userData["wechat"][] = $memberItem["login_name"];
                    }
                }
            }
        }
        return $userData;
    }

    /**
     * 添加数据
     * @param $data
     * @return array
     * @throws \Throwable
     */
    public function add($data)
    {
        $resData = $this->messageMemberDao->add($data);
        return $this->response($this->messageMemberDao, $resData);
    }

    /**
     * 查询多条 MessageMember
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function selectMessageMember($param)
    {
        $resData = $this->messageMemberDao->select($param);
        return $resData;
    }

    /**
     * 消息修改为已读状态
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function read($param)
    {
        // 获取属于我的message
        $messageMemberData = $this->messageMemberDao->select($param);
        $messageMemberIds = array_column($messageMemberData["rows"], "id");

        if (!empty($messageMemberIds)) {
            $filter["filter"] = [
                "message_member" => [
                    "id" => ["IN", join(",", $messageMemberIds)]
                ]
            ];
            $data['status'] = "read";
            $resData = $this->messageMemberDao->update($filter, $data);
            return $this->response($this->messageMemberDao, $resData);
        } else {
            return ["status" => 404];
        }
    }
}