<?php
namespace app\extend\tb;

use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;
use app\extend\tb\TbUtil;

/**
 * 获取图片空间某个文件夹下的内容
 * 使用的接口: https://stream.taobao.com/api/get_files.api
 * 当前(2021-12-24)的返回格式是:
    [
        'object' => [xxx],
        'total' => 21,
        'hasNext' => true,
        'success' => true,
        'status' => 0
    ]
 * 
 */
class TbSucaiFiles {

    
    // 判断淘宝返回数据是否可用
    private function isApiOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);

        } catch (\Throwable $th) { $input = null; }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        
        // 通过success判断接口是否正常返回, 没有该字段, 无法判断
        if (!array_key_exists('success', $input)) return 'SYSTEM_ERROR';


        return $input['success']
            ? true
            : 'SYSTEM_ERROR';
    }


    // 入口
    public function handle ($param) {

        [
            'cookie' => $cookie,
            'page' => $page,
            'pageSize' => $pageSize,
            'folderId' => $folderId
        ] = $param;

        $url = 'https://stream.taobao.com/api/get_files.api';
        $_tb_token = TbUtil::getTbToken($cookie);
        $query_arr = [
            // 固定
            '_input_charset' => 'utf-8',
            'searchKey' => '',
            '_input_charset' => 'utf-8',
            'appkey' => 'tu',
            'sort' => '1',
            'version' => '1',
            
            // 动态
            'folderId' => $folderId,
            '_tb_token_' => $_tb_token,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
        $query = Util::arrayToQueryString($query_arr);
        $url = "$url?$query";


        // 发起请求
        $doCurl = new DoCurl([ 'cookie' => $cookie ]);
        $response = $doCurl->get("$url?$query");
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
            
            $result_data = [];
            $result_data['object'] = $raw['object'];
            $result_data['total'] = $raw['total'];
            $result_data['hasNext'] = $raw['hasNext'];

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