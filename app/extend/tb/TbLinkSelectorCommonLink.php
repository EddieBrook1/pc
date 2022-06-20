<?php
namespace app\extend\tb;

use app\extend\DoCurl;
use app\extend\tb\TbUtil;
use app\extend\Util;
use app\extend\RecordError;


/**
 * 链接选择器-常用链接
 * 使用的接口: https://wangpu.taobao.com/shopdecorate/ajax/tool/getCommonLinkList.do
 * 当前(2021-12-24)的返回格式:
    [
        'isSuccess' => true,
        'module' => [xxxx]
    ]
 * 
 * 注意: $response['raw'] 是jsonp格式的返回值, 需正则提取出数据转数组后才能得到这个格式
 */
class TbLinkSelectorCommonLink {

    // jsonp格式转数组
    private function jsonpToArray ($raw, $jsonp_callback_name) {

        // 正则提取jsonp数据, 如果返回值是  fn1({"name": "lee"}), 那么将提取 {"name": "lee"} 部分
        preg_match('/^'. $jsonp_callback_name .'\((.*)\)$/', $raw, $p);
        return json_decode($p[1], true);
    }


    // 数据是否返回正常
    private function isApiOk ($raw, $jsonp_callback_name) {

        // 尝试转成数组
        try {
            
            $input = $this->jsonpToArray($raw, $jsonp_callback_name);

        } catch (\Throwable $th) { $input = null; }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        // 通过isSuccess判断接口是否正常返回, 没有该字段, 无法判断
        if (!array_key_exists('isSuccess', $input)) return 'SYSTEM_ERROR';

        return $input['isSuccess']
            ? true
            : 'SYSTEM_ERROR';
    }


    // 入口
    public function handle ($cookie) {

        $url = 'https://wangpu.taobao.com/shopdecorate/ajax/tool/getCommonLinkList.do';
        $_tb_token_ = TbUtil::getTbToken($cookie);
        $jsonp_callback_name = 'reqwest_1640243680090';
        $query_arr = [
            // 固定
            '_input_charset' => 'utf-8',
            'cilent' => 'mobile',
            'clientType' => '0',
            'callback' => $jsonp_callback_name,

            '_tb_token_' => $_tb_token_,
        ];

        // 数组转查询字符串
        $query = Util::arrayToQueryString($query_arr);
        $url = "$url?$query";
        
        // 发起请求
        $doCurl = new DoCurl([
            'cookie' => $cookie,
            'header' => [ 'referer: https://wuxian.taobao.com/' ],
        ]);
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
        $err_name = $this->isApiOk($raw, $jsonp_callback_name);
        if ($err_name !== true) {

            RecordError::handle(
                __CLASS__,
                '数据: "' . $raw .'"',
                '淘宝返回数据异常'
            );
            return Util::formatResult([], false, $err_name);
        }


        // jsonp格式转数组
        $raw = $this->jsonpToArray($raw, $jsonp_callback_name);
        try {
            
            $result_data = $raw['module']['content'];
            
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