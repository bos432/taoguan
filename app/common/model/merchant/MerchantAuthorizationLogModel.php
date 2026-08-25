<?php

declare(strict_types=1);

namespace app\common\model\merchant;

use think\Model;

class MerchantAuthorizationLogModel extends Model
{
    protected $name = 'merchant_authorization_log';
    protected $pk = 'id';
}
