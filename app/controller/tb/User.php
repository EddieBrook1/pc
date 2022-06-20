<?php
namespace app\controller\tb;

use think\Request;
use think\facade\Validate;

use app\BaseController;
use app\extend\Util;
use app\extend\tb\TbUtil;
use app\model\OldUserModel;

class User extends BaseController {


    // 更新客户cookie
    public function updateCookie (Request $request) {

        // 入参检查
        $input = $request->post();
        $isOk = UserValidator::updateCookie($input);
        if ($isOk !== true) return $this->resultJson([], false, 'PARAM_ERR');

        $user_id = $input['user_id'];
        $cookie = $input['cookie'];

        // user_id是否存在
        $user = OldUserModel::where('user_id', $user_id)->find();
        if ($user == null) return $this->resultJson([], false, 'USER_UNDEFINED');

        // 格式化cookie, 以防cookie格式有问题导致的错误
        $cookie = Util::formatCookie($cookie);

        // 更新cookie
        $user->cookie = $cookie;
        $user->save();

        // 派发cookie更新事件
        event('UpdateUserCookie', $user->user_id);

        return $this->resultJson([], true, 'UPDATE_SUCC');
    }


    // 检查客户cookie是否可用
    public function checkTbAuth (Request $request) {

        $uid = $request->uid;
        $cookie = OldUserModel::field('cookie')
            ->where('user_id', $uid)
            ->find()
            ->cookie;

        $is_cookie_ok = TbUtil::isCookieValid($cookie);
        return $is_cookie_ok
            ? $this->resultJson([], true, 'TB_LOGIN_OK')
            : $this->resultJson([], true, 'TB_UN_LOGIN');
    }
}


class UserValidator {

    public static function updateCookie ($input) {

        $validate = Validate::rule([
            'user_id' => 'require|Number',
            'cookie' => 'require',
        ]);

        if (!$validate->check($input)) return '参数错误';
        return true;
    }
}