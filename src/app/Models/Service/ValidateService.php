<?php

namespace app\Models\Service;

use app\Models\Dao\KeyPairDao;

class ValidateService extends BaseService
{

    /**
     * @var \app\Models\Dao\KeyPairDao
     */
    protected $keyPairDao;

    /**
     *
     * @param $context
     */
    public function initialization(&$context)
    {
        parent::initialization($context);

        $this->keyPairDao = $this->loader->model(KeyPairDao::class, $this);
    }

    /**
     * 生成密钥对
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function generate()
    {
        $findData = $this->keyPairDao->find();

        $updateData = [
            "access_key" => md5(create_uuid() . '_access_key'),
            "secret_key" => md5(create_uuid() . '_secret_key')
        ];

        if (!empty($findData)) {
            // 更新数据
            $filter = [
                "id" => ["=", $findData["id"]]
            ];
            $this->keyPairDao->update(generate_filter("key_pair", $filter), $updateData);
        } else {
            // 新增数据
            $this->keyPairDao->add($updateData);
        }

        return $updateData;
    }

    /**
     * 验证令牌
     * @param $token
     * @return bool
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function checkToken($token)
    {
        $keyPairData = $this->keyPairDao->find();
        if (!empty($keyPairData)) {
            $correct = md5($keyPairData["access_key"] . $keyPairData["secret_key"]);
            if ($correct === $token) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取密钥对
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function getKeyPairData()
    {
        $filter = [
            "id" => ["=", 1]
        ];;
        return $this->keyPairDao->find(generate_filter("key_pair", $filter));
    }
}