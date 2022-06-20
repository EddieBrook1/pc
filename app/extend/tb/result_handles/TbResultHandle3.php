<?php
namespace app\extend\tb\result_handles;


/**
 * 适用于
 * https://tadget.taobao.com/redaction/redaction/json.json?xxxx
 */
class TbResultHandle3 {
    

    public function isOk ($raw) {

        // 尝试转换成数组
        try {

            $raw = json_decode($raw, true);
        } catch (\Throwable $th) {

            $raw = null;
        }
        // 无法转成数组, 有问题, 退出
        if (!is_array($raw)) return 'SYSTEM_ERROR';


        // 判断是否有这些键名, 无法判断接口是否正常返回值
        if (!array_key_exists($raw['message'])) return 'API_ERROR';

        
        // 期望有这些值
        $success    = $raw['success'];
        $errorCode  = $raw['errorCode'];
        $message    = $raw['message'];

        // 正常
        if ($success) return true;


        // ---异常, 挨个调用验证方法
        // $isOk = $this->xxx($errorCode, $message);
        // if ($isOk !== true) return $isOk;

        // $isOk = $this->xxx($errorCode, $message);
        // if ($isOk !== true) return $isOk;


        // 默认返回系统错误
        return 'SYSTEM_ERROR';
    }
}