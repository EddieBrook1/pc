<?php
namespace app\extend\wxapp;

use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;

class OpenId {

    private function isApiOk ($raw) {
        
        // 尝试转换成数组
        try {
            $input = json_decode($raw, true);
        } catch (\Throwable $th) { $input = null; }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        // 判断是否有 errcode 字段, 有就不行
        if (isset($input['errcode'])) return 'GET_FAIL';

        return true;
    }


    public function handle ($input) {

        $code = $input['code'];
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $query_arr = [
            'appid' => config('wxapp.app_id'),
            'secret' => config('wxapp.app_secret'),
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $query = Util::arrayToQueryString($query_arr);
        $url = "$url?$query";

        $doCurl = new Docurl;
        $response = $doCurl->get($url);
        $isOk = $response['isOk'];
        $raw = $response['raw'];
        
        // 请求异常
        if (!$isOk) {
            RecordError::handle(
                __CLASS__,
                '请求异常',
                '微信小程序code换取openId失败'
            );
            return Util::formatResult([], false, 'SYSTEM_ERROR');
        }

        // 接口异常, 提前返回
        $err_name = $this->isApiOk($raw);
        if ($err_name !== true) {
            RecordError::handle(
                __CLASS__,
                '数据: "' . $raw . '"',
                '微信接口返回数据异常',
            );
            return Util::formatResult([], false, $err_name);
        }
        
        $raw = json_decode($raw, true);
        try {
            
            $result_data = ['open_id' => $raw['openid']];
            
        } catch (\Throwable $th) {
            RecordError::handle(
                __CLASS__,
                '数据: "' . json_encode($raw) . '"',
                '微信接口返回数据格式改变'
            );
            $result_data = null;
        }

        // 获取不到值, 返回错误
        if ($result_data == null) return Util::formatResult([], false, 'SYSTEM_ERROR');

        // 返回
        return Util::formatResult($result_data, true);
    }

}