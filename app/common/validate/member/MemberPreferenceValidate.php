<?php

namespace app\common\validate\member;

use think\Validate;

class MemberPreferenceValidate extends Validate
{
    protected $rule = [
        'preference_group' => ['require', 'length' => '1,64'],
        'preference_key' => ['require', 'length' => '1,128'],
        'preference_keys' => ['require', 'array'],
        'items' => ['require', 'array'],
        'value_type' => ['require', 'in' => 'json,string,int,float,bool'],
    ];

    protected $message = [
        'preference_group.require' => 'preference_group is required',
        'preference_group.length' => 'preference_group length must be 1-64',
        'preference_key.require' => 'preference_key is required',
        'preference_key.length' => 'preference_key length must be 1-128',
        'preference_keys.require' => 'preference_keys is required',
        'preference_keys.array' => 'preference_keys must be an array',
        'items.require' => 'items is required',
        'items.array' => 'items must be an array',
        'value_type.require' => 'value_type is required',
        'value_type.in' => 'value_type is invalid',
    ];

    protected $scene = [
        'list' => ['preference_group'],
        'save' => ['preference_group', 'preference_key', 'value_type'],
        'batchSave' => ['preference_group', 'items'],
        'dele' => ['preference_group', 'preference_keys'],
        'clear' => ['preference_group'],
    ];
}
