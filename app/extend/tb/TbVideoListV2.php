<?php
namespace app\extend\tb;

use app\extend\tb\TbUtil;
use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;


/**
 * 获取视频列表
 * 使用的接口: https://ugc.taobao.com/sucai/ajax/videoQuery1.do
 * 当前(2021-12-29)的返回格式是:
    [
        'msg' => '查询视频信息成功',
        'trace' => 'xxxx',
        'code' => '200',
        'model' => [xxx],
    ]
 * 该接口返回的是gbk格式, 需转成utf-8才能正常使用
 */
class TbVideoListV2 {


    private function isApiOk ($raw) {

        // 尝试转成数组
        try {
            
            $raw = iconv('GB2312', 'UTF-8', $raw);
            $input = json_decode($raw, true);

        } catch (\Throwable $th) { $input = null; }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        
        // 通过msg和code判断接口是否正常返回, 没有该字段, 无法判断
        if (!array_key_exists('code', $input)) return 'SYSTEM_ERROR';

        
        $code = $input['code'];

        // ---接口返回正常
        if ($code === '200') {

            return true;
        } else {

            return 'SYSTEM_ERROR';
        }
    }


    // 入口
    public function handle ($cookie, $pageNum, $pageSize) {

        $url = 'https://ugc.taobao.com/sucai/ajax/videoQuery1.do';
        $_tb_token = TbUtil::getTbToken($cookie);
        $query_arr = [
            // 固定
            '_input_charset' => 'utf-8',
            'type' => 'mobile',
            'sort' => 'uploadTime',

            // 动态
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
            '_tb_token_' => $_tb_token,
        ];
        $query = Util::arrayToQueryString($query_arr);
        $url = "$url?$query";


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


        $raw = json_decode(iconv('GB2312', 'UTF-8', $raw), true);
        try {
            
            $result_data = $raw['model'];
            
        } catch (\Throwable $th) {

            RecordError::handle(
                __CLASS__,
                '数据: "'. json_encode($raw) .'"',
                '淘宝接口返回数据格式改变'
            );
            $result_data = null;
        }

        // 获取不到值, 返回错误
        if ($result_data == null) return Util::formatResult([], false, 'SYSTEM_ERROR');

        // 返回
        return Util::formatResult($result_data, true);
    }
}