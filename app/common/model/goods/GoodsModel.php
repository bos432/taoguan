<?php
namespace app\common\model\goods;
use app\common\model\file\FileModel;
use app\common\model\file\MerchantFileModel;
use app\common\model\member\MemberModel;
use app\common\model\merchant\MerchantModel;
use app\common\model\setting\SettingCallModel;
use app\common\model\setting\SettingHallModel;
use think\facade\Log;
use think\facade\Db;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class GoodsModel extends Model
{
    // 表名
    protected $name = 'goods';
    // 表主键
    protected $pk = 'id';
    //商品状态:0、待审核 1、审核通过 2、审核失败
    const STATUS = [
        ['value' => 0, 'label' => '待审核','code' => 'auth'],
        ['value' => 1, 'label' => '审核通过','code' => 'auth_success'],
        ['value' => 2, 'label' => '审核失败','code' => 'auth_error'],
    ];

    /**
     * @title: 查询状态
     * @author：易军辉
     * @date：2024/10/29
     * @param $key
     * @param int $type 1、查询value  2、查询名称 3、查询编码
     * @return mixed|void
     */
    public static  function getStatus($key,$type=1)
    {
        foreach (self::STATUS as $status) {
            if ($status['code'] == $key || $status['value'] == $key) {
                switch ($type) {
                    case 1:
                        return $status['value']; // 返回value
                    case 2:
                        return $status['label']; // 返回名称
                    case 3:
                        return $status['code']; // 返回code
                    default:
                        return null;
                }
            }
        }
    }

    // 关联图片
    public function image()
    {
        $model = $this->resolveFileModelClass();
        return $this->hasOne($model, 'file_id', 'image_id')->append(['file_url'])->where(where_disdel());
    }

    private function resolveFileModelClass(): string
    {
        return intval($this->source ?? 0) === 1 ? MerchantFileModel::class : FileModel::class;
    }

    private function resolveFileRowsByIds(array $fileIds = []): array
    {
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if (empty($fileIds)) {
            return [];
        }

        $primaryModel = $this->resolveFileModelClass();
        $fallbackModel = $primaryModel === FileModel::class ? MerchantFileModel::class : FileModel::class;
        $rows = [];

        foreach ([$primaryModel, $fallbackModel] as $modelClass) {
            $loadedRows = $modelClass::whereIn('file_id', $fileIds)
                ->where(where_disdel())
                ->append(['file_url'])
                ->select()
                ->toArray();

            foreach ($loadedRows as $row) {
                $fileId = intval($row['file_id'] ?? 0);
                if ($fileId > 0 && !isset($rows[$fileId])) {
                    $rows[$fileId] = $row;
                }
            }
        }

        $result = [];
        foreach ($fileIds as $fileId) {
            if (isset($rows[$fileId])) {
                $result[] = $rows[$fileId];
            }
        }

        return $result;
    }
    /**
     * 获取图片链接
     * @Apidoc\Field("")
     * @Apidoc\AddField("image_url", type="string", desc="图片链接")
     */
    public function getImageUrlAttr($value, $data)
    {
        $fileUrl = $this['image']['file_url'] ?? '';
        if ($fileUrl !== '') {
            return $fileUrl;
        }

        $imageId = intval($data['image_id'] ?? 0);
        if ($imageId <= 0) {
            return '';
        }

        $files = $this->resolveFileRowsByIds([$imageId]);
        return $files[0]['file_url'] ?? '';
    }

    // 关联分类
    public function type()
    {
        return $this->hasOne(GoodsTypeModel::class, 'id','goods_type_id');
    }
    /**
     * 获取分类名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getTypeTitleAttr()
    {
        $title = $this['type']['title'] ?? '';
        return $title;
    }
    // 关联文件
    public function files()
    {
        $model = $this->resolveFileModelClass();
        return $this->belongsToMany($model, GoodsImagesModel::class, 'image_id', 'goods_id')->where(where_disdel());
    }
    // 获取图片文件列表
    public function getImagesAttr()
    {
        if (!empty($this['files'])) {
            return $this['files']->append(['file_url'])->toArray();
        }

        $goodsId = intval($this['id'] ?? 0);
        if ($goodsId <= 0) {
            return [];
        }

        $imageIds = Db::name('goods_images')
            ->where('goods_id', $goodsId)
            ->column('image_id');

        return $this->resolveFileRowsByIds($imageIds);
    }
    // 获取图片文件id
    public function getImageIdsAttr()
    {
        return array_values(array_map(function ($file) {
            return intval($file['file_id'] ?? 0);
        }, $this->getImagesAttr()));
    }
    // 关联商家
    public function merchant()
    {
        return $this->hasOne(MerchantModel::class, 'id','merchant_id');
    }
    /**
     * 获取商家名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getMerchantTitleAttr()
    {
        if (intval($this['merchant_id'] ?? 0) === 0) {
            return '平台自营';
        }
        $settingInfo = \app\common\service\system\SettingService::info('wx_approved');
        if($settingInfo['wx_approved']==1){
            return '平台自营';
        }
        $title = $this['merchant']['title'] ?? '';
        return MerchantModel::formatDisplayTitle($title);
    }
    // 关联大厅
    public function hall()
    {
        return $this->hasOne(SettingHallModel::class, 'id','setting_hall_id');
    }
    /**
     * 获取大厅名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getHallTitleAttr()
    {
        $title = $this['hall']['title'] ?? '';
        return $title;
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
    // 关联秤
    public function call()
    {
        return $this->hasOne(SettingCallModel::class, 'id','setting_call_id');
    }
    /**
     * 获取秤名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getCallTitleAttr()
    {
        $title = $this['call']['title'] ?? '';
        return $title;
    }
}
