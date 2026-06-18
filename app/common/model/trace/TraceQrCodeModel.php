<?php
namespace app\common\model\trace;
use app\common\model\goods\GoodsModel;
use think\Model;

class TraceQrCodeModel extends Model
{
    // 表名
    protected $name = 'trace_qr_code';
    // 表主键
    protected $pk = 'id';
    // 生成指定长度的随机防伪码（包含大小写字母和数字）
    private static function generateRandomCode($length = 18) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $randomCode = '';
        for ($i = 0; $i < $length; $i++) {
            $randomCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomCode;
    }
    /**
     * @title:批量生成防伪码
     * @author：易军辉
     * @date：2024/12/5
     * @param $batchSize 生成数量
     * @param $codeLength 防伪码长度
     * @return array
     */
    public static function generateBatchAntiFakeCodes($batchSize = 0, $codeLength = 18)
    {
        $codes = [];
        $batchToGenerate = $batchSize;

        while (count($codes) < $batchSize) {
            // 临时批量生成防伪码
            $newCodes = [];
            while (count($newCodes) < $batchToGenerate) {
                $code = self::generateRandomCode($codeLength);
                $newCodes[] = $code;
            }

            // 查询数据库中是否有重复的防伪码
            $existingCodes = self::whereIn('code', $newCodes)->column('code');

            // 过滤掉已存在的防伪码
            $newUniqueCodes = array_diff($newCodes, $existingCodes);

            // 将唯一的新防伪码加入到最终列表
            $codes = array_merge($codes, $newUniqueCodes);

            // 计算剩余需要生成的数量
            $batchToGenerate = $batchSize - count($codes);
        }

        return $codes;
    }
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
}
