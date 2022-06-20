<?php
namespace app\http;

use app\extend\RecordError;

// workman的定时器回调里不会抛出错误, 因此手动写一个错误包裹, 让回调在该函数包裹后执行, 有错误自动记录
class ErrorWrap {

    public static function handle ($current_class, $fn) {

        return function () use ($current_class, $fn) {

            try {
            
                $fn();
            } catch (\Throwable $th) {
    
                RecordError::handle($current_class, $th, 'workman定时器报错');
            }
        };
    }
}