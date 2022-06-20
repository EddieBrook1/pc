<?php
namespace app\controller\tb;

use app\BaseController;
use think\Request;
use think\facade\Validate;
use app\model\OldUserModel;
use app\extend\RecordError;


class Login extends BaseController {

    private const TOKEN_TIMEOUT = 120;

    // 通过临时token登录
    public function byToken (Request $request) {

        // 验证入参, 静默失败
        $input = $request->get();
        $isOk = LoginController::byToken($input);
        if (!$isOk) {
            RecordError::handle(
                __CLASS__,
                "参数错误::$isOk",
                '通过token登录失败'
            );
            return $this->resultJson([], false, 'PARAM_ERR');
        }

        $token = $input['token'];
        $succ_url = isset($input['succ_url']) ? $input['succ_url'] : '';
        $fail_url = isset($input['fail_url']) ? $input['fail_url'] : '';

        $user = OldUserModel::where('sso_token', $token)->find();

        // token不存在或已超时, 静默失败
        if ($user == null || $user->sso_token_time - time() > self::TOKEN_TIMEOUT) {
            RecordError::handle(
                __CLASS__,
                "token不存在或已超时",
                '通过token登录失败'
            );
            return $fail_url
                ? redirect($fail_url)
                : $this->resultJson([], false, 'TOKEN_ERR');
        }

        // 提取用户信息, 生成session
        session('uid', $user->user_id);
        session('uid_update_time', time());

        // 删除临时生成的token值
        $user->sso_token = '';
        $user->save();

        return $succ_url
            ? redirect($succ_url)
            : $this->resultJson([], true, 'EXEC_SUCC');
    }
}


class LoginController {

    public static function byToken ($input) {

        $validate = Validate::rule([
            'token' => 'require',
            'succ_url' => 'url',
            'fail_url' => 'url',
        ]);

        return $validate->check($input);
    }

}