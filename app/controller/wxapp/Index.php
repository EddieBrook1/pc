<?php
namespace app\controller\wxapp;

use think\Request;
use think\facade\Validate;
use app\BaseController;
use app\extend\wxapp\OpenId;

class Index extends BaseController {

    public function openId (Request $request) {

        $input = $request->get();
        if (!IndexValidator::openId($input)) return $this->resultJson([], false, 'PARAM_ERR');

        $result = (new OpenId)->handle($input);
        return $result['error']
            ? $this->resultJson([], false, $result['code_name'])
            : $this->resultJson($result['data'], true, 'GET_SUCC');
    }

}


class IndexValidator {

    public static function openId ($input) {

        $validate = Validate::rule([
            'code' => 'require',
        ]);

        return $validate->check($input);
    }

}