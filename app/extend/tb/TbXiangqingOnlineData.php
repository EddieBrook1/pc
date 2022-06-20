<?php
namespace app\extend\tb;

use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;

class TbXiangqingOnlineImgs {


    // 淘宝接口返回是否正常可用
    private function isApiOk ($raw) {

        preg_match('/登陆已过期/', $raw, $p);
        if (!empty($p)) return 'TB_UN_LOGIN';

        return true;
    }


    private function formatHTML ($html) {

        $DOM = new \DOMDocument;
        $DOM->loadHTML($html);
        $DOM->normalizeDocument();
        $imgs = $DOM->getElementById('detail')->getElementsByTagName('img');
        

        $result = [];
        foreach ($imgs as $img) {
            array_push($result, $img->getAttribute('src'));
        }

        return $result;
    }


    public function handle ($cookie, $goods_id) {

        $url = "https://xiangqing.taobao.com/template/convert.htm?itemId=$goods_id";

        // 发起请求
        $doCurl = new DoCurl([ 'cookie' => $cookie ]);
        $response = $doCurl->get($url);
        $isOk = $response['isOk'];
        $raw = $response['raw'];


        // 请求有问题, 提前退出
        if (!$isOk) {

            RecordError::handle(
                __CLASS__,
                "链接($url)",
                '淘宝接口http请求错误'
            );
            return Util::formatResult([], false, 'SYSTEM_ERROR');
        }


        // 淘宝接口返回异常, 提前返回
        $err_name = $this->isApiOk($raw);
        if ($err_name !== true) {

            RecordError::handle(
                __CLASS__,
                '数据: "' . $raw .'"',
                '淘宝返回数据异常'
            );
            return Util::formatResult([], false, $err_name);
        }


        try {
            
            $result_data = $this->formatHTML($raw);
            
        } catch (\Throwable $th) {
            RecordError::handle(
                __CLASS__,
                '数据: "'. json_encode($raw) .'"',
                '淘宝接口返回数据转换失败'
            );
            $result_data = null;
        }

        // 获取不到值, 返回错误
        if ($result_data == null) return Util::formatResult([], false, 'SYSTEM_ERROR');

        // 返回
        return Util::formatResult($result_data, true);
    }
}