<?php


namespace app\common\model\member;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 会员接口模型
 */
class ApiModel extends Model
{
    // 表名
    protected $name = 'member_api';
    // 表主键
    protected $pk = 'api_id';
}
