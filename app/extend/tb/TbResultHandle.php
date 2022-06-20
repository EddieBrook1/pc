<?php
namespace app\extend\tb;


/**
 * 适用于
 * https://xiangqing.wangpu.taobao.com/img/get_folder.htm?_input_charset=utf-8
 * https://xiangqing.wangpu.taobao.com/img/media_upload.htm?_input_charset=utf-8&_tb_token_=xxxx
 * https://xiangqing.wangpu.taobao.com/template/ajax/commit_item_template.do?_input_charset=utf-8&_tb_token_=xxxxx
 */
class TbResultHandle {

    private $handle_names = [
        'isLoginOk',
        'isPicSpaceFull',
    ];


    // 是否登录
    private function isLoginOk ($code, $msg) {

        preg_match('/请先登录/', $msg, $p);
        return ($code === 1 && !empty($p))
            ? 'TB_UN_LOGIN'
            : true;
    }


    // 是否图片空间满了
    private function isPicSpaceFull ($code, $msg) {

        preg_match('/容量不足，请登录图片空间（tu.taobao.com）清理图片或订购存储功能包/', $msg, $p);
        return ($code === 1000 && !empty($p))
            ? 'TB_PIC_SPACE_FULL'
            : true;
    }


    public function isOk ($raw) {

        $error = $raw['error'];
        $code = $raw['code'];
        $msg = $raw['msg'];
        $handle_names = $this->handle_names;

        // 正常
        if ($code === 0 && $error === false) return true;

        // 异常
        foreach ($handle_names as $method_name) {

            $err_name = $this->$method_name($code, $msg);
            if ($err_name !== true) return $err_name;
        }

        // 默认返回系统错误
        return 'SYSTEM_ERROR';
    }
}