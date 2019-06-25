<?php

namespace app\Models\Service;

use app\Models\Dao\ConfigDao;

class ConfigService extends BaseService
{

    /**
     * @var \app\Models\Dao\ConfigDao
     */
    protected $configDao;

    /**
     * @param $context
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(&$context)
    {
        parent::initialization($context);

        // 初始化验证器
        $this->configDao = $this->loader->model(ConfigDao::class, $this);
    }

    /**
     * 更新配置
     * @param $data
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function updateConfig($data)
    {
        $filter = [
            "name" => ["=", $data["name"]]
        ];
        $param = generate_filter("config", $filter);

        // 查询是否已经存在数据
        $findData = $this->configDao->find($param);

        if (!empty($findData)) {
            // 更新数据
            $resData = $this->configDao->update($param, $data);
        } else {
            // 新增数据
            $resData = $this->configDao->add($data);
        }

        return $this->response($this->configDao, $resData);
    }

    /**
     * 获取配置
     * @param $name
     * @return array|string
     * @throws \Throwable
     */
    public function getConfig($name)
    {
        $filter = [
            "name" => ["=", $name]
        ];

        $param = generate_filter("config", $filter);

        $findData = $this->configDao->find($param);

        if(!empty($findData)){
            return json_decode($findData["config"], true);
        }

        return [];
    }
}