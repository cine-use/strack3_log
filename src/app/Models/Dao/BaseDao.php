<?php

namespace app\Models\Dao;

use Server\CoreBase\Model;
use Server\Memory\Pool;

/**
 * 基类model
 * Class BaseModel
 * @package app\Models
 */
class BaseDao extends Model
{

    // 操作状态
    const MODEL_INSERT = 1; //  插入模型数据
    const MODEL_UPDATE = 2; //  更新模型数据
    const MODEL_BOTH = 3; //  包含上面两种方式
    const MUST_VALIDATE = 1; // 必须验证
    const EXISTS_VALIDATE = 0; // 表单存在字段则验证
    const VALUE_VALIDATE = 2; // 表单值不为空则验证

    //当前表
    public $table;

    // 自动完成数据
    public $_auto;

    // 验证器
    public $validate;

    //错误信息
    protected $errorMsg = "";

    /**
     * @param $context
     */
    public function initialization(&$context)
    {
        parent::initialization($context);
    }

    /**
     * 初始化验证器
     * @param $validate
     * @throws \Server\CoreBase\SwooleException
     */
    public function initValidate($validate)
    {
        $this->validate = Pool::getInstance()->get("\\app\\Validate\\{$validate}");
    }

    /**
     * 还验证器对象
     * @throws \Server\CoreBase\SwooleException
     */
    public function pushValidate()
    {
        Pool::getInstance()->push($this->validate);
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->errorMsg;
    }

    /**
     * 数据自动完成
     * @access public
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return mixed
     */
    protected function dataAutoComplete($data, $type)
    {
        $_auto = $this->_auto;
        // 自动填充
        if (isset($_auto)) {
            foreach ($_auto as $auto) {
                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if ($auto[2] !== 0 && empty($auto[2])) {
                    $auto[2] = self::MODEL_INSERT;
                }

                // 默认为新增的时候自动填充
                if ($type == $auto[2] || self::MODEL_BOTH == $auto[2] || (self::EXISTS_VALIDATE == $auto[2] && array_key_exists($auto[0], $data))) {
                    if (empty($auto[3])) {
                        $auto[3] = 'string';
                    }

                    switch (trim($auto[3])) {
                        case 'function'://  使用函数进行填充 字段的值作为参数
                        case 'callback':    // 使用回调方法
                            $args = isset($auto[4]) ? (array)$auto[4] : array();
                            if (isset($data[$auto[0]])) {
                                array_unshift($args, $data[$auto[0]]);
                            }
                            if ('function' == $auto[3]) {
                                $data[$auto[0]] = call_user_func_array($auto[1], $args);
                            } else {
                                $data[$auto[0]] = call_user_func_array(array(&$this, $auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore':    // 为空忽略
                            if ($auto[1] === $data[$auto[0]]) {
                                unset($data[$auto[0]]);
                            }

                            break;
                        case 'string':
                        default:    // 默认作为字符串填充
                            $data[$auto[0]] = $auto[1];
                    }
                    if (isset($data[$auto[0]]) && false === $data[$auto[0]]) {
                        unset($data[$auto[0]]);
                    }

                }
            }
        }
        return $data;
    }


    /**
     * 组装过滤条件 {"filter":{ "event_log":{"operate":[  "LIKE", "ww" ] }
     * @param $param
     * @return $this
     */
    protected function assemblyFilter($param)
    {
        //"IN","NOT IN"的值转为数组
        $requireArrayList = ["IN", "BETWEEN", "NOT BETWEEN", "NOT IN"];
        if (array_key_exists("filter", $param) && !empty($param["filter"][$this->table])) {
            $filter = $param["filter"][$this->table];
            foreach ($filter as $field => list($logic, $value)) {
                if (in_array($logic, $requireArrayList) && !is_array($value)) {
                    $value = explode(",", $value);
                }
                if (in_array($logic, ["LIKE", "NOT LIKE"])) {
                    $value = "%$value%";
                }
                $this->db->where("`$field`", $value, $logic);
            }
        }
        return $this;
    }

    /**
     * 设置字段
     * @param $param
     * @return $this
     */
    protected function setFields($param)
    {
        if (array_key_exists("fields", $param) && array_key_exists($this->table, $param["fields"]) && !empty($param["fields"][$this->table])) {
            $fields = implode(",", $param["fields"][$this->table]);
        } else {
            $fields = "*";
        }
        $this->db->select($fields);
        return $this;
    }

    /**
     * 设置分页
     * @param $param
     * @return $this
     */
    protected function setPage($param)
    {
        if (isset($param['page']) && array_key_exists('page_number', $param['page']) && $param['page']['page_number'] !== 0 && array_key_exists('page_size', $param['page']) && $param['page']["page_size"] !== 0) {
            $pageSize = intval($param['page']['page_size']);
            $pageNumber = intval($param['page']["page_number"]);
            $this->db->limit($pageSize, ($pageNumber - 1) * $pageSize);
        } else {
            $this->db->limit(2000);
        }
        return $this;
    }

    /**
     * 设置排序
     * @param $param
     * @return $this
     */
    protected function setOrder($param)
    {
        if (array_key_exists("order", $param) && !empty($param["order"])) {
            $this->db->orderBy(array_keys($param["order"])[0], array_values($param["order"])[0]);
        }
        return $this;
    }

    /**
     * 获取查询数据总数
     * @param $param
     * @return int
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function getTotalNumber($param)
    {
        $this->db->from($this->table);
        $this->assemblyFilter($param);
        $total = $this->db->select('id')->query()->num_rows();
        return $total;
    }

    /**
     * 查找数据    (["filter"=>[],"fields"=>[],"page"=>"","order"=>[]])
     * @param array $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function select($param = [])
    {
        // 获取总行数
        $total = $this->getTotalNumber($param);

        // 查询数据
        $this->db->from($this->table);
        $this->assemblyFilter($param)
            ->setFields($param)
            ->setPage($param)
            ->setOrder($param);

        $rows = $this->db->query()->getResult();

        return [
            "total" => $total,
            "rows" => $rows["result"]
        ];
    }

    /**
     * 单条查找
     * @param array $param
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function find($param = [])
    {
        // 查询数据
        $this->db->from($this->table);

        // 设置过滤条件和字段
        $this->assemblyFilter($param)
            ->setFields($param);

        $resData = $this->db
            ->limit(1)
            ->query()
            ->row();

        if (!empty($resData)) {
            return $resData;
        }

        return [];
    }

    /**
     * 添加数据
     * @param $data
     * @return bool|mixed
     * @throws \Throwable
     */
    public function add($data)
    {
        // 验证和自动完成数据
        $this->validate->scene('create');
        if (!$this->validate->check($data)) {
            $this->errorMsg = $this->validate->getError();
            return false;
        }

        $this->pushValidate();
        $data = $this->dataAutoComplete($data, self::MODEL_INSERT);

        // 添加数据
        $resData = $this->db->insert($this->table)
            ->set($data)
            ->query();

        if ($resData->affected_rows() > 0) {
            return $resData->getResult();
        } else {
            $this->errorMsg = "create failure.";
            return false;
        }
    }

    /**
     * 更新指定字段数据
     * @param $param
     * @param $data
     * @return bool
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function update($param, $data)
    {
        // 验证数据和数据自动完成
        $this->validate->scene('update');
        if (!$this->validate->check($data)) {
            $this->errorMsg = $this->validate->getError();
            return false;
        }

        $this->pushValidate();

        $data = $this->dataAutoComplete($data, self::MODEL_UPDATE);

        //更新数据
        $this->db->update($this->table);
        $this->assemblyFilter($param);

        $resData = $this->db->set($data)
            ->query();

        if ($resData->affected_rows() > 0) {
            return $data;
        } else {
            $this->errorMsg = "update failure.";
            return false;
        }
    }

    /**
     * 删除数据
     * @param $param
     * @return bool
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function delete($param)
    {
        $this->db->delete($this->table);
        $this->assemblyFilter($param);

        $resData = $this->db->query();

        if ($resData->affected_rows() > 0) {
            return true;
        } else {
            $this->errorMsg = "delete failure.";
            return false;
        }
    }
}
