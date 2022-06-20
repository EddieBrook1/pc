<?php
namespace app\extend\tb;

use app\extend\DoCurl;
use app\extend\Util;
use app\extend\RecordError;


/**
 * 上传图片到淘宝的图片空间
 * 使用的接口: https://stream-upload.taobao.com/api/upload.api
 * 当前(2021-12-24)的返回格式:
    [
        'hasNext' => true,
        'message' => '',
        'object' => [xxxx],
        'status' => 0,
        'success' => true,
        'total' => 0,
        'errorCode': 'XXXX',
    ]
 * 
 * 其中object只有在请求正常时存在, errorCode只有在异常时存在
 */
class TbStreamUpload {

    /**
     * 验证接口返回值是否可用
     * 可能返回的错误代码
     *      SYSTEM_ERROR
     *      UPLOAD_FILENAME_ERR
     *      TB_PIC_SPACE_FULL
     */
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
        // 通过 errorCode 和 message 判断接口是什么异常, 没有这些字段无法判断
        if (
            !array_key_exists('errorCode', $input) ||
            !array_key_exists('message', $input)
        ) return 'SYSTEM_ERROR';


        $errorCode = $input['errorCode'];
        $message = $input['message'];

        // 文件名错误
        preg_match('/文件名称包含特殊字符/', $message, $p);
        if ($errorCode === 'FILE_NAME_CONTAIN_SPECIAL_CHARACTER' && !empty($p)) return 'UPLOAD_FILENAME_ERR';
        
        // 图片空间满了
        preg_match('/容量不足/', $message, $p);
        if ($errorCode === 'USER_CAPACITY_EXCEED' && !empty($p)) return 'TB_PIC_SPACE_FULL';

        // 没匹配上, 返回系统错误
        return 'SYSTEM_ERROR';
    }


    /**
     * 入口
     *
     * @param [Array] $param  形如:
     *      @param mixed $param['cookie']  用户cookie
     *      @param mixed $param['pic_path']  图片本地地址
     *      @param mixed $param['folder_id'] 淘宝图片空间文件夹地址
     * 
     * @return Util::formatResult 的返回值, 如果请求成功data形如:
        [
            // 淘宝图片id
            'fileId' => xxx,
            // 图片所在文件夹id
            'folderId' => xxx,
            // 图片地址
            'url' => xxx,
            // 文件名, 和上次时的名称一样
            'fileName' => xxx,
            // 图片大小
            'size' => xxx,
            // 图片宽高
            'pix' => xxx,
        ]
     */
    public function handle ($param) {
        
        // 解构
        [
            // 客户cookie
            'cookie' => $cookie,
            // 图片本地地址
            'pic_path' => $pic_path,
            // 文件夹id
            'folder_id' => $folder_id,
        ] = $param;

        // 接口
        $url = 'https://stream-upload.taobao.com/api/upload.api';

        // 上传图片附加的数据
        $query_arr = [
            // 固定
            'appkey'         => 'tu',
            'watermark'      => false,
            'autoCompress'   => false,
            '_input_charset' => 'utf-8',

            // 动态
            'folderId' => $folder_id,
        ];
        $query = Util::arrayToQueryString($query_arr);

        // 拼接参数
        $url = "$url?$query";
        
        // 文件的名称, 固定值, 即$_FILES[filename] 中的 filename 值
        $file_field = 'file';

        // 发起请求
        $doCurl = new DoCurl([ 'cookie' => $cookie ]);
        $response = $doCurl->upload($url, $pic_path, $file_field);

        $isOk = $response['isOk'];
        $raw = $response['raw'];


        // 请求有问题, 提前退出
        if (!$isOk) {

            RecordError::handle(
                __CLASS__,
                "链接($url), 图片本地地址($pic_path)",
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
            
            $result_data = $raw['object'];

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