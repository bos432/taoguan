<?php
namespace app\common\model\trace;
use app\common\model\goods\GoodsModel;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class TraceBatchTacheModel extends Model
{
    // 表名
    protected $name = 'trace_batch_tache';
    // 表主键
    protected $pk = 'id';
    // 关联商品
    public function goods()
    {
        return $this->hasOne(GoodsModel::class, 'id','goods_id');
    }
    /**
     * 获取商品名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="商品名称")
     */
    public function getGoodsTitleAttr()
    {
        $title = $this['goods']['title'] ?? '';
        $spec = $this['goods']['spec'] ?? '';
        $unit = $this['goods']['unit'] ?? '';
        if($spec){
            $title .="(".$spec.")";
        }
        if(!$spec && $unit){
            $title .="(".$unit.")";
        }
        return $title;
    }
    // 关联批次
    public function batch()
    {
        return $this->hasOne(TraceBatchModel::class, 'id','trace_batch_id');
    }
    /**
     * 获取批次号
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="批次号")
     */
    public function getBatchTitleAttr()
    {
        $title = $this['batch']['title'] ?? '';
        return $title;
    }
    // 关联环节
    public function tache()
    {
        return $this->hasOne(TraceTacheModel::class, 'id','trace_tache_id');
    }
    /**
     * 获取环节号
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="批次号")
     */
    public function getTacheTitleAttr()
    {
        $title = $this['tache']['title'] ?? '';
        return $title;
    }
    // 关联属性值
    public function tacheValue()
    {
        return $this->hasMany(TraceBatchTacheValueModel::class, 'trace_batch_tache_id', 'id');
    }
}
