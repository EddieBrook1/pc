<?php
namespace app\extend;

use think\facade\Log;
use think\facade\Session;


class Util {

    // 图片转64码
    public static function base64EncodeImage ($image_file) {

        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = file_get_contents($image_file);
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }


    /**
     * 通过key获取cookie字符串中的某一项
     *
     * @param [String] $key cookie名称
     * @param [String] $cookie 格式正确的cookie, 形如: name=lee; age=18
     * @return void
     */
    public static function getCookieByKey ($key, $cookie) {

        $exploded = \explode('; ', $cookie);
        foreach ($exploded as $item) {

            $item_arr = \explode('=', $item);
            if ($item_arr[0] == $key) {

                return $item_arr[1];
            }
        }

        return null;
    }


    /**
     * 数组转成query字符串
     *
     * @param [Array] $arr
     * @return String
     */
    public static function arrayToQueryString ($arr) {

        $query1 = [];
        foreach ($arr as $key => $value) {
            
            $cur = "$key=$value";
            array_push($query1, $cur);
        }

        return implode('&', $query1);
    }


    /**
     * 格式化cookie, 去掉多余的分号(;), cookie之间以分号+空格(; )分割
     *
     * @param [String] $cookie
     * @return String
     */
    public static function formatCookie ($cookie) {

        $exploded = explode(';', $cookie);
        $arr = [];
        foreach ($exploded as $item) {
            
            $trimed = trim($item);
            if (strlen($trimed) == 0) continue;
            array_push($arr, $trimed);
        }

        return implode('; ', $arr);
    }


    /**
     * 格式化内部类或对外接口的返回值
     *
     * @param array $data       负载数据
     * @param boolean $isOk     执行结果是否正常, true正常, false异常
     * @param string $code_name 异常代码名, 见 config/code.php 文件
     * @return Array 形如:
        正常返回值
        [
            'error' => false,
            'code' => xxx,
            'msg' => xxx,
            'data' => xxx,
        ]
        // 异常返回值
        [
            'error' => true,
            'code' => xxx,
            'msg' => xxx,
            'data' => xxx,
        ]
     * 
     */
    public static function formatResult ($data = [], $isOk = true, $code_name = 'DEFAULT', $more_msg = '') {

        $isOk = boolval($isOk);

        $code_value = config("code.$code_name");
        if ($code_value == null) $code_value = config('code.DEFAULT');
        
        $code = $code_value['code'];
        $msg = $code_value['msg'];

        if (strlen($more_msg) > 0) {
            $msg = "$msg::$more_msg";
        }

        $result = [
            'error' => !$isOk,
            'code' => $code,
            'code_name' => $code_name,
            'msg' => $msg,
            'data' => $data,
        ];

        return $result;
    }


    // 根据code获取code_name
    public static function getCodeNameByCode ($code) {

        $codes = config('code');
        foreach ($codes as $code_name => $item) {

            if ($item['code'] == $code) return $code_name;
        }

        return null;
    }


    // 根据code获取msg
    public static function getCodeMsgByCode ($code) {

        $codes = config('code');
        foreach ($codes as $code_name => $item) {

            if ($item['code'] == $code) return $item['msg'];
        }

        return null;
    }


    /**
     * 判断用户是否登录
     * 如果登录返回true, 否则返回fales
     * @return boolean
     */
    public static function isLogin () {

        $uid = Session::get('uid');
        return !($uid == null);
    }


    // 获取已登录用户的uid
    public static function getLoggedUid () {

        return Session::get('uid');
    }


    /**
     * 碰撞检测
     * A是被拖动元素
     * B是被碰撞的元素
     * 见[https://www.cnblogs.com/momen/p/9450394.html]
     */
    public static function checkCrash($Ax, $Ay, $Awidth, $Aheight, $Bx, $By, $Bwidth, $Bheight){
    
        $bool = true;
        if (
            ($Ax + $Awidth) < $Bx ||
            ($Bx + $Bwidth) < $Ax ||
            ($Ay + $Aheight) < $By||
            ($By + $Bheight) < $Ay
        ) {
            
            $bool = false;
        }

        return $bool;
    }


    // JSONP数据转数组, 转换失败返回null
    public static function jsonpToArray ($raw, $jsonp_callback_name) {

        // 正则提取jsonp数据, 如果返回值是  fn1({"name": "lee"}), 那么将提取 {"name": "lee"} 部分
        try {
            
            $raw = trim($raw);
            preg_match('/^'. $jsonp_callback_name .'\((.*)\)$/', $raw, $p);
            $result = json_decode($p[1], true);
        } catch (\Throwable $th) {
            
            $result = null;
        }

        return $result;
    }
}