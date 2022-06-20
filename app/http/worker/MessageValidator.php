<?php
namespace app\http\worker;

use think\facade\Log;
use think\facade\Validate;

class MessageValidator {

    /**
     * 登录数据验证, $data形如:
        [
            'access_token' => 'xxxxx'
        ]
     * 
     */
    private function _login ($data) {

        if (!isset($data['uid'])) return false;
        if (!is_numeric($data['uid'])) return false;

        return true;
    }


    // 心跳数据验证
    private function _heartbeat ($data) {

        return true;
    }

    
    public function handle ($input) {

        // 必须要有这几个键
        if (
            !array_key_exists('msg_id', $input) ||
            !array_key_exists('handle', $input) ||
            !array_key_exists('data', $input)
        ) return false;


        // 验证格式
        $validate = Validate::rule([
            'handle' => 'require',
            'data' => 'array',
            'msg_id' => 'require',
        ]);
        if (!$validate->check($input)) return false;


        $handle = $input['handle'];
        $msg_id = $input['msg_id'];
        $data = $input['data'];

        
        // 为避免碰上关键词, 加上前缀
        $handle = "_$handle";
        // 判断handle是否存在
        if (!method_exists($this, $handle)) return false;

        // 调用对应方法验证数据格式
        return $this->$handle($data);
    }
}