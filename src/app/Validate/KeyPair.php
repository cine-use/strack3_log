<?php

namespace app\Validate;

class KeyPair extends Base
{
    // 验证规则
    protected $rule = [
        'id' => 'number',
        'access_key' => 'require|max:128',
        'secret_key' => 'require|max:128',
    ];

    // 验证场景
    protected $scene = [
        'create' => [
            'access_key',
            'secret_key',
        ],
        'update' => [
            "access_key" => 'max:128',
            "secret_key" => "max:128"
        ]
    ];
}