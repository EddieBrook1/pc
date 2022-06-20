<?php
namespace app\extend\tb;

use app\extend\DoCurl;
use app\extend\tb\TbUtil;
use app\extend\Util;
use app\extend\RecordError;


/**
 * 获取图片空间的文件夹树
 * 使用的接口: https://tadget.taobao.com/redaction/redaction/json.json
 * 当前(2021-12-24)的返回格式是:
    [
        'success' => true,
        'message' => null,
        'module' => [xxx],
        'crsToken' => '',
    ]
 * 
 */
class TbSucaiDirTree {

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


        // 接口返回正常
        if ($input['success']) return true;


        // ---接口返回异常
        // 通过 message 判断接口是什么异常, 没有这个字段无法判断
        if (!array_key_exists('message', $input)) return 'SYSTEM_ERROR';
        $message = $input['message'];


        // 未登录或登录过期
        preg_match('/用户登陆已过期/', $message, $p);
        if (!empty($p)) return 'TB_UN_LOGIN';
        
        
        // 没匹配上, 返回系统异常
        return 'SYSTEM_ERROR';
    }


    /**
     * 入口
     *
     * @param [String] $cookie 用户cookie
     * @return Util::formatResult 的返回值, 如果请求成功data形如:
        [
            // 所有文件夹数量
            'total' => xxx,
            // 文件夹数据
            'dirs' => [
                'name' => '全部图片',
                'id' => 0,
                'open' => true,
                'type' => '2',
                'children' => [
                    'name' => 'xxx',
                    'id' => xxxxxx,
                    'open' => false,
                    'type' => '2',
                    'children' => [...以此类推]
                ]
            ]
        ]
     *
     */
    public function handle ($cookie) {

        $url = 'https://tadget.taobao.com/redaction/redaction/json.json';
        $_tb_token_ = TbUtil::getTbToken($cookie);
        $query = "cmd=json_dirTree_query&count=true&_input_charset=utf-8&_tb_token_=$_tb_token_";
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
            
            $result_data = $raw['module'];

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