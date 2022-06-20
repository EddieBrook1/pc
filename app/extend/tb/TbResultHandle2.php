<?php
namespace app\extend\tb;


/**
 * 适用于
 * https://tadget.taobao.com/redaction/redaction/json.json?xxxx
 */
class TbResultHandle2 {
    

    private $handle_names = [
        'isLoginOk',
    ];


    // 登录是否失效
    private function isLoginOk ($message) {

        preg_match('/用户登陆已过期/', $message, $p);
        return !empty($p)
            ? 'TB_UN_LOGIN'
            : true;
    }


    public function isOk ($raw) {

        $success = $raw['success'];
        $message = $raw['message'];
        $handle_names = $this->handle_names;

        // 正常
        if ($success === true) return true;

        // 异常
        foreach ($handle_names as $method_name) {

            $err_name = $this->$method_name($message);
            if ($err_name !== true) return $err_name;
        }

        // 默认返回系统错误
        return 'SYSTEM_ERROR';
    }
}