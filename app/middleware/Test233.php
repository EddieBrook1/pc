<?php
namespace app\middleware;

use app\extend\Util;
use app\model\OldQhLoginTokenModel;

class Test233
{

    /**
     * 检查用户是否登录
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {

        header('Access-Control-Allow-Origin: *');

        var_dump($_SERVER);

        return $next($request);
    }
}
