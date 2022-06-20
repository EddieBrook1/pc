<?php
namespace app\middleware;


// 只允许内部ip访问
class OnlyInsideIp
{

    /**
     * 只允许内部ip访问
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if ($_SERVER['REMOTE_ADDR'] === config('app.ip')) {
            return $next($request);
        } else {
            http_response_code(404);exit;
        }
    }
}
