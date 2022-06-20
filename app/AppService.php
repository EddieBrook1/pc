<?php
declare (strict_types = 1);

namespace app;

use think\Service;
use think\facade\Validate;

/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        // 服务注册
    }

    public function boot()
    {
        // 服务启动

        // 自定义验证规则
        Validate::maker(function ($validate) {

            // 验证是否为字符串
            $validate->extend('string', function ($value) {

                return is_string($value);
            }, ':attribute 不是字符串');

            // 验证数组每一项是否都为字符串
            $validate->extend('array_str', function ($arr) {

                if (!is_array($arr)) return false;

                foreach ($arr as $str) {

                    if (!is_string($str)) return false;
                }

                return true;
            }, ':attribute 不都为字符串');
        });
    }
}
