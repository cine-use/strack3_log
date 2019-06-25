<?php

namespace app\Models\Service;

use Server\CoreBase\Model;

class BaseService extends Model
{

    /**
     *
     * @param $context
     */
    public function initialization(&$context)
    {
        parent::initialization($context);
    }

    /**
     * 服务层返回数据处理
     * @param $object
     * @param $data
     * @return array
     */
    public function response($object, $data)
    {
        if($data === false){
            return ["status" => 404, "message" => $object->getError(), "data" => []];
        }else{
            return ["status" => 200, "message" => '', "data" => $data];
        }
    }
}