<?php

namespace app\Models\Dao;

class KeyPairDao extends BaseDao
{

    /**
     * MediaModel constructor.
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
        $this->table = 'key_pair';

        // 获取验证器对象
        $this->initValidate("KeyPair");
    }

}