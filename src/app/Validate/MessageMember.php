<?php
/**
 * Created by PhpStorm.
 * User: weijer
 * Date: 2018/10/26
 * Time: 21:17
 */

namespace app\Validate;

class MessageMember extends Base
{
    //验证规则
    protected $rule = [
        'id' => 'number',
        'message_id' => 'require|number',
        'status' => 'require|in:read,unread',
        'user_id' => 'require|number',
        'name' => 'require|max:255',
        'email' => 'require|max:128',
        'user_uuid' => 'require|max:36',
        'belong_type' => 'require|in:cc,assign,producer,coordinator,at',
        'json' => 'require|array',
        'created_by' => 'require|number'
    ];

    //验证场景
    protected $scene = [
        'create' => [
            'message_id',
            'status',
            'user_id',
            'name',
            'email',
            'user_uuid',
            'belong_type',
            'json',
            'created_by'
        ],
        'update' => [
            'id' => 'number',
            'status' => 'in:read,unread',
            'message_id' => 'number',
            'user_id' => 'number',
            'name' => 'max:255',
            'email' => 'max:128',
            'user_uuid' => 'max:36',
            'belong_type' => 'in:cc,assign,producer,coordinator,at',
            'json' => 'array',
            'created_by' => 'number'
        ]
    ];
}