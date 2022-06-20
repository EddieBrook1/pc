<?php
namespace app\middleware;

use app\extend\Util;
use app\model\OldQhLoginTokenModel;

class CheckLogin
{

    // token过期时间
    private const TOKEN_TIMEOUT = 60 * 60 * 3;


    /**
     * 检查用户是否登录
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {

        $qh_token = $request->header('qh-token');
        $qhLoginToken = OldQhLoginTokenModel::where('token', $qh_token)->find();

        // 未登录
        if (!$qhLoginToken) return json(Util::formatResult([], false, 'UN_LOGIN'));

        // token过期,　未登录
        if (time() - $qhLoginToken->token_update_time > self::TOKEN_TIMEOUT) return json(Util::formatResult([], false, 'UN_LOGIN'));

        // 记录该用户
        $request->uid = $qhLoginToken->uid;

        return $next($request);
    }
}
