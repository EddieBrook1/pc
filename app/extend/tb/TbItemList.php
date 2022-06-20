<?php
namespace app\extend\tb;

use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;


/**
 * 获取商品列表
 * 使用到的接口: https://xiangqing.wangpu.taobao.com/item/ajax/ItemList.do
 * 当前(2021-12-24)的返回格式:
    [
        'authentication' => null,
        'code' => 0,
        'data' => [xxx],
        'debug' => null,
        'error' => false,
        'msg' => 'success',
        'ok' => true,
        'type' => 0,
    ]
 * 
 */
class TbItemList {


    // 淘宝接口返回是否正常可用
    private function isApiOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);
        } catch (\Throwable $th) { $input = null; }
        

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';


        // 通过这3个字段判断是否正常, 没有该字段, 无法判断
        if (
            !array_key_exists('error', $input) ||
            !array_key_exists('code', $input) ||
            !array_key_exists('msg', $input)
        ) return 'SYSTEM_ERROR';

        
        // 接口返回正常
        if ($input['error'] === false && $input['code'] === 0) return true;


        // 接口返回异常
        $code = $input['code'];
        $msg = $input['msg'];

        // 未登录或登录过期
        preg_match('/请先登录/', $msg, $p);
        if ($code === 1 && !empty($p)) return 'TB_UN_LOGIN';


        // 没匹配上, 返回系统异常
        return 'SYSTEM_ERROR';
    }


    // 入口
    public function handle ($param) {

        [
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
            'online' => $online,
            'q' => $q,
            'cookie' => $cookie,
        ] = $param;

        $url = 'https://xiangqing.wangpu.taobao.com/item/ajax/ItemList.do';
        $query_arr = [
            // 固定
            '_input_charset' => 'utf-8',

            // 动态
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
        ];
        if ($online) $query_arr['online'] = $online;    // 出售中还是仓库中
        if ($q) $query_arr['q'] = $q;                   // 查询条件

        // 数组转查询字符串
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


        $raw = json_decode($raw, true);
        try {
            
            $result_data = $raw['data'];

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