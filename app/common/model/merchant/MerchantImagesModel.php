<?php


namespace app\common\model\merchant;

use think\model\Pivot;

/**
 * 资质图片关联模型
 */
class MerchantImagesModel extends Pivot
{
    // 表名
    protected $name = 'merchant_images';
}
