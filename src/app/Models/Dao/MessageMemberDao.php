<?php

namespace app\Models\Dao;

class MessageMemberDao extends BaseDao
{

    // 自动完成
    public $_auto = [
        ['json', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['created', 'time', self::MODEL_INSERT, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];

    /**
     * MessageMemberModel constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->loader->mysql('mysqlPool', $this);
    }

    /**
     * 初始化
     * @param $context
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(&$context)
    {
        parent::initialization($context);

        // 当前模型绑定表名
        $this->table = 'message_member';

        // 获取验证器对象
        $this->initValidate("MessageMember");
    }
}