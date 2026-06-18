<?php

namespace app\common\model\merchant;
use app\common\model\file\FileModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberModel;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class MerchantModel extends Model
{
    // 表名
    protected $name = 'merchant';
    // 表主键
    protected $pk = 'id';
    const AUTH_STATE = [
        ['value' => 0, 'label' => '待审核','code' => 'wait'],
        ['value' => 1, 'label' => '审核通过','code' => 'success'],
        ['value' => 2, 'label' => '审核失败','code' => 'error'],
    ];
    /**
     * 查询状态
     * @Author: 易军辉
     * @DateTime:2024-12-06 16:10
     * @param $key 编码或value
     * @param $type 1、查询value  2、查询名称 3、查询编码
     * @return mixed|void
     */
    public static  function getAuthState($key,$type=1)
    {
        foreach (self::AUTH_STATE as $status) {
            if ($status['code'] == $key || $status['value'] == $key) {
                switch ($type) {
                    case 1:
                        return $status['value']; // 返回value
                    case 2:
                        return $status['label']; // 返回名称
                    case 3:
                        return $status['code']; // 返回code
                    default:
                        return $status; // 未知类型，返回对象
                }
            }
        }
    }
    // 关联文件
    public function files()
    {
        $model =FileModel::class;
        return $this->belongsToMany($model, MerchantImagesModel::class, 'image_id', 'merchant_id');
    }
    // 获取图片文件列表
    public function getImagesAttr()
    {
        if ($this['files']) {
            $files = $this['files']->append(['file_url'])->toArray();
            foreach ($files as $file) {
                $images[] = $file;
            }
        }
        return $images ?? [];
    }
    // 获取图片文件id
    public function getImageIdsAttr()
    {
        if ($this['files']) {
            $files = $this['files']->append(['file_url'])->toArray();
            foreach ($files as $file) {
                $image_ids[] = $file['file_id'];
            }
        }
        return $image_ids ?? [];
    }

    // 关联收款信息
    public function image()
    {
        $model = FileModel::class;
        return $this->hasOne($model, 'file_id', 'image_id')->append(['file_url'])->where(where_disdel());
    }
    /**
     * 获取收款信息链接
     * @Apidoc\Field("")
     * @Apidoc\AddField("image_url", type="string", desc="图片链接")
     */
    public function getImageUrlAttr($value, $data)
    {
        $file_url = $this['image']['file_url'] ?? '';
        return $file_url;
    }

    /**
     * 获取商家收款码链接
     * 现阶段沿用 image_id 作为商家收款码来源，避免影响现网已存在数据。
     */
    public function getReceiptImageUrlAttr($value, $data)
    {
        return $this->getImageUrlAttr($value, $data);
    }
    // 关联会员
    public function member()
    {
        return $this->hasOne(MemberModel::class, 'member_id','member_id');
    }
    /**
     * 获取商家名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getMemberTitleAttr()
    {
        $title = $this['member']['nickname'] ?? '';
        return $title;
    }
    public function getMemberPhoneAttr()
    {
        $title = $this['member']['phone'] ?? '';
        return $title;
    }

    // 关联售卖商品
    public function sellGoods()
    {
        return $this->hasMany(GoodsModel::class, 'merchant_id', 'id')->where([where_disdel()])->where('status',1)->where('stock','>',0);
    }
    public static function formatDisplayTitle(string $title = ''): string
    {
        $title = trim($title);
        $length = mb_strlen($title);
        if ($length <= 1) {
            return $title;
        }

        return mb_substr($title, 0, 1) . '***' . mb_substr($title, -1, 1);
    }

    public function getMemberIsSuperTitleAttr($value, $data)
    {
        return intval($data['member_is_super'] ?? 0) === 1 ? '是' : '否';
    }
}
