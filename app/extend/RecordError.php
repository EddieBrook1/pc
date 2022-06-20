<?php
namespace app\extend;

use think\facade\Log;


/**
 * 记录自定义类抛出的错误
 */
class RecordError {

    // 记录 Exception 抛出的错误
    public static function handle ($class_name, $e, $prefix = '自定义类抛错') {
        
        if ($e instanceof \Throwable) {

            $message = $e->getMessage();
            $code = $e->getCode();
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = "Exception(message: $message; code: $code; file: $file; line: $line;)";
        } else {

            $msg = strval($e);
        }

        Log::record("[$prefix: $class_name]: $msg");
    }
}