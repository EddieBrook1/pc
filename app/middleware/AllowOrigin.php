<?php
namespace app\middleware;

use app\extend\Util;
use app\model\OldQhLoginTokenModel;

class AllowOrigin
{

    private const ALLOW_LIST = [
        'http://qianzhiyu.net',
        'http://www.qianzhiyu.net',
        'http://test233.ppd369.top',
    ];


    /**
     * 跨域检查
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {

        
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE");
        header("Access-Control-Allow-Headers: QH-TOKEN, Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With, Cache-Control");

        return $next($request);

        // if (isset($_SERVER['HTTP_ORIGIN'])) {
        //     $http_origin = $_SERVER['HTTP_ORIGIN'];
        //     if (in_array($http_origin, self::ALLOW_LIST)) {
        //         header("Access-Control-Allow-Origin: $http_origin");
        //         header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE");
        //         header("Access-Control-Allow-Headers: QH-TOKEN, Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With, Cache-Control");
        //         return $next($request);
        //     }
        // } else {
        //     return $next($request);
        // }
        
    }
}
