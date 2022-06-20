<?php
namespace app\extend\tb;

use app\extend\DoCurl;
use app\extend\RecordError;
use app\extend\tb\TbUtil;
use app\extend\Util;


/**
 * 上传图片到淘宝的图片空间
 * 使用的接口: https://xiangqing.wangpu.taobao.com/img/media_upload.htm
 * 当前(2021-12-24)的返回格式:
    [
        'authentication' => null,
        'code' => 0,
        'data' => [xxxx],
        'debug' => null,
        'error' => false,
        'msg' => '',
        'ok' => true,
        'type' => 0,
    ]
 * 
 */
class TbMediaUpload {


    // 判断淘宝返回数据是否正常
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

        // 图片空间满了
        preg_match('/容量不足，请登录图片空间（tu.taobao.com）清理图片或订购存储功能包/', $msg, $p);
        if ($code === 1000 && !empty($p)) return 'TB_PIC_SPACE_FULL';

        // 没匹配上, 返回"系统异常"
        return 'SYSTEM_ERROR';
    }


    /**
     * 入口
     *
     * @param [Array] $param  形如:
     *      @param mixed $param['cookie']  用户cookie
     *      @param mixed $param['pic_name']  图片名
     *      @param mixed $param['pic_path']  图片本地地址
     *      @param mixed $param['goods_id']  商品id
     *      @param mixed $param['folder_id'] 淘宝图片空间文件夹地址
     * 
     * @return Util::formatResult 的返回值, 如果请求成功data形如:
        [
            // 淘宝图片id
            'id' => xxx,
            // 图片名称, 跟上传时的参数一致
            'name' => xxx,
            // 图片字节大小
            'size' => xxx,
            // 不知道是什么
            'tfsName' => xxx,
            // 图片后缀,　.jpg .png
            'type' => xxx,
            // 淘宝图片地址
            'url' => xxx,
        ]
     */
    public function handle ($param) {

        // 入参
        [
            // 客户cookie
            'cookie' => $cookie,
            // 图片名称
            'pic_name' => $pic_name,
            // 图片本地地址
            'pic_path' => $pic_path,
            // 商品id
            'goods_id' => $goods_id,
            // 文件夹id
            'folder_id' => $folder_id,
        ] = $param;


        // 接口
        $_tb_token_ = TbUtil::getTbToken($cookie);        
        $url = "https://xiangqing.wangpu.taobao.com/img/media_upload.htm?_input_charset=utf-8&_tb_token_=$_tb_token_";


        // 上传图片附加的数据
        $post_data = [
            // 图片名称
            'name' => $pic_name,
            // 图片后缀, 形如: .jpg
            'type' => '.' . pathinfo($pic_path)['extension'],
            // 商品id
            'item_id' => $goods_id,
            // 文件夹id
            'folderId' => $folder_id,
            // 固定值
            'code' => 'file',
            // 固定值
            'op' => 'common',
        ];

        // 文件的名称, 固定值. 即 $_FILES[filename]  中的 filename 值
        $file_field = 'f';

        // 除了cookie外, 其他的请求头
        $http_header = [
            ':authority: xiangqing.wangpu.taobao.com',
            ':method: POST',
            ":path: /img/media_upload.htm?_input_charset=utf-8&_tb_token_=$_tb_token_",
            'origin: https://xiangqing.wangpu.taobao.com',
            "referer: https://xiangqing.wangpu.taobao.com/new_user_panel.htm?itemId=$goods_id&templateId=0&clientType=0&freeTry=0",
        ];

        // 发送请求
        $doCurl = new DoCurl([
            'cookie' => $cookie,
            'header' => $http_header,
            'auto_redirect' => false,
        ]);
        $response = $doCurl->upload($url, $pic_path, $file_field, $post_data);

        $isOk = $response['isOk'];
        $raw = $response['raw'];

        var_dump($response);

        // 请求异常, 提前返回
        if (!$isOk) {

            RecordError::handle(
                __CLASS__,
                "链接($url)",
                '淘宝接口http请求错误'
            );
            return Util::formatResult([], false, 'SYSTEM_ERR');
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