<?php

namespace app\Models\Dao;

class MessageDao extends BaseDao
{

    // 自动完成
    public $_auto = [
        ['content', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['sender', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];

    /**
     * MessageModel constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = $this->loader->mysql('mysqlPool', $this);
    }


    /**
     * @param $context
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(&$context)
    {
        parent::initialization($context);

        // 当前模型绑定表名
        $this->table = 'message';

        // 获取验证器对象
        $this->initValidate("Message");
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorMsg;
    }
}