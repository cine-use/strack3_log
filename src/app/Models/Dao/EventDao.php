<?php

namespace app\Models\Dao;

class EventDao extends BaseDao
{
    // 自动完成
    public $_auto = [
        ['record', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['created', 'time', self::MODEL_INSERT, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];

    /**
     * EventModel constructor.
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
        $this->table = 'event_log';

        // 获取验证器对象
        $this->initValidate("Event");
    }
}