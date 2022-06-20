<?php
namespace app\extend\tb;

use app\extend\RecordError;
use app\extend\DoCurl;
use app\extend\Util;
use app\extend\tb\TbUtil;



/**
 * 获取用户上传图片时用的文件夹id
 * 使用的接口: https://xiangqing.wangpu.taobao.com/img/get_folder.htm
 * 当前(2021-12-24)的返回格式:
 * 
    [
        "authentication" => null
        "code" => 0
        "data" => "xxxxx"
        "debug" => null
        "error" => false
        "msg" => ""
        "ok" => true
        "type" => 0
    ]
 * 
 */
class TbGetFolder {


    // 判断淘宝返回数据是否可用
    private function isApiOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);

        } catch (\Throwable $th) { $input = null; }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        // 通过这三个字段判断, 缺少一个无法判断
        if (
            !array_key_exists('error', $input) ||
            !array_key_exists('code', $input) ||
            !array_key_exists('msg', $input)
        ) return 'SYSTEM_ERROR';

        $error = $input['error'];
        $code = $input['code'];
        $msg = $input['msg'];

        // 正常
        if ($code === 0 && $error === false) return true;


        // --- 异常

        // 未登录
        preg_match('/请先登录/', $msg, $p);
        if ($code === 1 && !empty($p)) return 'TB_UN_LOGIN';

        // 没匹配上, 返回"系统异常"
        return 'SYSTEM_ERROR';
    }


    /**
     * 入口方法
     *
     * @param [String] $cookie 用户的cookie
     * @return Util::formatResult  的返回值
     */
    public function handle ($cookie) {

        // 发起请求
        $url = 'https://xiangqing.wangpu.taobao.com/img/get_folder.htm?_input_charset=utf-8';
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