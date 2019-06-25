<?php


namespace app\Validate;

class Config extends Base
{
    // 验证规则
    protected $rule = [
        'id' => 'number',
        'name' => 'require|max:128',
        'config' => 'require|array',
    ];

    // 验证场景
    protected $scene = [
        'create' => [
            'name',
            'config',
        ],
        'update' => [
            "name" => 'max:128',
            "config" => "array"
        ]
    ];
}