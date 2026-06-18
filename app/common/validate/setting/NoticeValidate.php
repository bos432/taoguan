<?php


namespace app\common\validate\setting;

use think\Validate;

/**
 * 通告管理验证器
 */
class NoticeValidate extends Validate
{
    protected $rule = [
        'ids' => ['require', 'array'],
        'notice_id' => ['require'],
        'type' => ['require'],
        'title' => ['require'],
        'start_time' => ['require', 'date'],
        'end_time' => ['require', 'date'],
        'popup_enabled' => ['in' => '0,1'],
        'popup_frequency' => ['in' => 'once,daily,always'],
        'popup_scope' => ['in' => 'all,login,merchant,audit_admin'],
        'popup_jump_type' => ['in' => 'detail,url,mini_page,none'],
        'content_type' => ['in' => 'html'],
        'client_key' => ['length' => '0,64'],
        'read_type' => ['in' => 'popup_close,popup_view'],
    ];

    protected $message = [
        'title.require' => '请输入标题',
        'type.require' => '请选择类型',
        'start_time.require' => '请选择开始时间',
        'end_time.require' => '请选择结束时间',
        'popup_enabled.in' => '弹窗开关格式错误',
        'popup_frequency.in' => '弹窗频率格式错误',
        'popup_scope.in' => '弹窗范围格式错误',
        'popup_jump_type.in' => '跳转方式格式错误',
        'content_type.in' => '内容类型格式错误',
        'read_type.in' => '已读类型格式错误',
    ];

    protected $scene = [
        'info' => ['notice_id'],
        'add' => ['type', 'title', 'start_time', 'end_time', 'popup_enabled', 'popup_frequency', 'popup_scope', 'popup_jump_type', 'content_type'],
        'edit' => ['notice_id', 'type', 'title', 'start_time', 'end_time', 'popup_enabled', 'popup_frequency', 'popup_scope', 'popup_jump_type', 'content_type'],
        'dele' => ['ids'],
        'edittype' => ['ids', 'type'],
        'datetime' => ['ids', 'start_time', 'end_time'],
        'disable' => ['ids'],
        'popup_info' => ['client_key'],
        'popup_read' => ['notice_id', 'client_key', 'read_type'],
    ];
}
