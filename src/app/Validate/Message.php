<?php
/**
 * Created by PhpStorm.
 * User: weijer
 * Date: 2018/10/26
 * Time: 21:17
 */

namespace app\Validate;

class Message extends Base
{
    //验证规则
    protected $rule = [
        'id' => 'number',
        'operate' => 'require|max:128',
        'type' => 'require|in:system,at',
        'project_id' => 'require|number',
        'primary_id' => 'require|number',
        'module_id' => 'require|number',
        'emergent' => 'require|in:normal,emergent',
        'content' => 'require|array',
        'sender' => 'require|array',
        'from' => 'require|max:64',
        'email_template' => 'require|max:128',
        'identity_id' => 'require|max:64',
        'created_by' => 'require|number'
    ];

    //验证场景
    protected $scene = [
        'create' => [
            'operate',
            'type',
            'project_id',
            'primary_id',
            'module_id',
            'emergent',
            'content',
            'sender',
            'from',
            'email_template',
            'identity_id',
            'created_by'
        ],
        'update' => [
            'id' => 'number',
            'type' => 'in:system,at',
            'project_id' => 'number',
            'primary_id' => 'number',
            'module_id' => 'number',
            'emergent' => 'in:normal,emergent',
            'content' => 'array',
            'sender' => 'array',
            'from' => 'max:64',
            'email_template' => 'max:128',
            'identity_id' => 'max:64',
            'created_by' => 'number',
        ]
    ];
}