<?php
namespace app\extend\tb;

use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;
use app\extend\tb\TbUtil;


/**
 * 在淘宝图片空间里新建一个文件夹
 * 使用的接口: https://tadget.taobao.com/redaction/redaction/json.json
 * 当前(2021-12-24)的返回格式是:
    [
        'crsToken' => '',
        'errorCode' => null,
        'errorMessage' => null,
        'jsonData' => ['id' => xxxx ],
        'message' => null,
        'module' => [xxxx],
        'success' => true
    ]
 * 
 */
class TbSucaiAddDir {

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

        // 父目录Id不存在
        preg_match('/父目录Id不存在/', $message, $p);
        if (!empty($p)) return 'DIR_FID_NULL_ERR';
        
        // 文件夹命名重复
        preg_match('/在同一个目录下有相同的名字/', $message, $p);
        if (!empty($p)) return 'DIR_NAME_REPEAT_ERR';
        
        
        // 没匹配上, 返回系统异常
        return 'SYSTEM_ERROR';
    }


    /**
     * 入口
     *
     * @param [String] $cookie 用户的cookie
     * @param [Number] $dir_id 要在哪个文件夹下新建文件夹, 输入文件夹id
     * @param [String] $name 文件夹名称
     * @return Util::formatResult 的返回值, 如果请求成功data形如:
        [
            // 新增的文件夹id
            'folder_id' => xxx,
        ]
     */
    public function handle ($cookie, $dir_id, $name) {

        $url = 'https://tadget.taobao.com/redaction/redaction/json.json';
        $_tb_token_ = TbUtil::getTbToken($cookie);
        $jsonp_fn_name = 'reqwest_1638946297780';
        $query_arr = [
            // 固定
            'cmd'            => 'json_add_dir',
            '_input_charset' => 'utf-8',

            // 动态
            '_tb_token_' => $_tb_token_,
            'callback' => $jsonp_fn_name,
            'dir_id' => $dir_id,
            'name' => $name,
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
        
        
        $raw = json_decode($raw, true);
        try {
            
            $result_data = [ 'id' => $raw['jsonData']['id'] ];
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