<?php

namespace app\Models\Service;

use app\Models\Dao\EventDao;

class EventService extends BaseService
{

    /**
     * @var \app\Models\Dao\EventDao
     */
    protected $eventDao;

    /**
     *
     * @param $context
     */
    public function initialization(&$context)
    {
        parent::initialization($context);

        $this->eventDao = $this->loader->model(EventDao::class, $this);
    }


    /**
     * EventLog数据表字段类型
     */
    protected $fieldTypeConfig = [
        "id" => "int",
        "operate" => "string",
        "type" => "enum(built_in,custom)",
        "table" => "string",
        "project_id" => "int",
        "link_id" => "int",
        "module_id" => "int",
        "record" => "json",
        "from" => "string",
        "created_by" => "int",
        "created" => "int",
        "uuid" => "string"
    ];

    /**
     * 生成EventLog字段配置
     * @param $data
     * @return array
     */
    protected function generateFieldsConfig($data)
    {
        $fieldConfig = [
            "id" => $data["field"],
            "edit" => "deny",
            "lang" => $data["field"],
            "mask" => "",
            "show" => "yes",
            "sort" => "deny",
            "type" => $data["type"],
            "group" => "",
            "table" => "EventLog",
            "editor" => "text",
            "fields" => $data["field"],
            "filter" => "allow",
            "module" => "event_log",
            "multiple" => "no",
            "validate" => "",
            "module_code" => "eventlog",
            "field_type" => "built_in",
            "value_show" => $data["field"],
            "allow_group" => "deny",
            "is_primary_key" => "no"
        ];


        // id字段为主键
        if ($data["field"] === "id") {
            $fieldConfig["is_primary_key"] = "yes";
        }

        // 可以排序字段
        if (in_array($data["type"], ["string", "int"])) {
            $fieldConfig["sort"] = "allow";
            $fieldConfig["allow_group"] = "allow";
        }

        return $fieldConfig;
    }

    /**
     * 获取EventLog字段配置
     * @return array
     */
    public function getFieldsConfig()
    {
        $fieldConfig = [];
        foreach ($this->fieldTypeConfig as $key => $value) {
            array_push($fieldConfig, $this->generateFieldsConfig(["field" => $key, "type" => $value]));
        }

        $fieldsData = [
            "primary_key" => "id",
            "fixed_field" => $this->fieldTypeConfig,
            "config" => $fieldConfig
        ];

        return $fieldsData;
    }

    /**
     * 添加 Event
     * @param $data
     * @return array
     * @throws \Throwable
     */
    public function addEvent($data)
    {
        $resData = $this->eventDao->add($data);
        return $this->response($this->eventDao, $resData);
    }

    /**
     * 查询多条 Event
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function selectEvent($param)
    {
        $resData = $this->eventDao->select($param);
        return $resData;
    }


    /**
     * 查询但条 Event
     * @param $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function findEvent($param)
    {
        $resData = $this->eventDao->find($param);
        return $resData;
    }
}