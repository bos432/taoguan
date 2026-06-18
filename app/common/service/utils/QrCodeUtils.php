<?php


namespace app\common\service\utils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;

/**
 * 生成二维码
 */
class QrCodeUtils
{
    /**
     * 生成二维码图片
     * @Author: 易军辉
     * @DateTime:2024-09-28 19:13
     * @param $url 生成二维码的链接
     * @param $size 大小
     * @param $code 图片保存名称
     * @param $isHttp 是否添加域名
     * @return array|string|null
     * @throws \think\Exception
     */
    public function generateQrCodeUrl($url = '',$size=100,$code='qrcode',$isHttp=true)
    {
        if (!$url){
            return  null;
        }
        $result = Builder::create()
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->size($size)
            ->margin(1)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();
        // 获取当前日期的路径部分
        $datePath = 'storage/file/' . date('Ymd') . '/';
        // 检查目录是否存在
        if (!file_exists($datePath)) {
            // 如果不存在，则尝试创建目录
            // 第二个参数是可选的模式，默认为0777，第三个参数recursive设为true可以创建多级目录
            if (!mkdir($datePath, 0755, true)) {
                exception('无法创建目录，请联系管理员');
            }
        }
        $info = $datePath.$code.".png";
        $result->saveToFile($info);
        if($isHttp){
            $info = file_url($info);
        }
        return $info;
    }
}
