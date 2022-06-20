<?php
namespace app\middleware;

use think\facade\Session;
use app\extend\Util;

class CheckCsrf
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next) {

        $header = $request->header();
        $qianhui_csrf = isset($header['_qianhui_csrf_']) ? $header['_qianhui_csrf_'] : null;

        if ($qianhui_csrf === Session::get('_qianhui_csrf_')) {

            return $next($request);
        } else {

            return json(Util::formatResult([], false, 'ILLEGAL_REQUEST'));
        }
    }
}
