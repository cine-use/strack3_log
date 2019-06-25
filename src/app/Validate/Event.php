<?php
/**
 * Created by PhpStorm.
 * User: weijer
 * Date: 2018/10/26
 * Time: 21:17
 */

namespace app\Validate;

class Event extends Base
{
    //验证规则
    protected $rule = [
        'id' => 'number',
        'operate' => 'require|max:128',
        'type' => 'require|in:built_in,custom',
        'table' => 'require|max:36',
        'project_id' => 'number',
        'project_name' => 'max:128',
        'link_id' => 'require|number',
        'module_id' => 'require|number',
        'module_name' => 'require|max:128',
        'module_code' => 'require|max:128',
        'record' => 'require|array',
        'from' => 'require|max:64',
        'created_by' => 'require|max:128'
    ];

    //验证场景
    protected $scene = [
        'create' => [
            'operate',
            'type',
            'table',
            'project_id',
            'project_name',
            'link_id',
            'module_id',
            'module_name',
            'module_code',
            'record',
            'from',
            'created_by'
        ],
        'update' => [
            'id' => 'number',
            'operate' => 'max:128',
            'type' => 'in:built_in,custom',
            'table' => 'max:36',
            'project_id' => 'number',
            'project_name' => 'max:128',
            'link_id' => 'number',
            'module_id' => 'number',
            'module_name' => 'max:128',
            'module_code' => 'max:128',
            'record' => 'array',
            'from' => 'max:64',
            'created_by' => 'max:128'
        ]
    ];
}