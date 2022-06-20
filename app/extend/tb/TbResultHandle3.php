<?php
namespace app\extend\tb;


/**
 * 淘宝接口返回值异常处理类
 * isOk为入口方法, 通过 $type 入参调用对应的名为 HandleX 的处理类
 * HandleX处理类视作是 TbResultHandle3 的子集, 每个子处理类处理一种类型的返回值
 * 每个 HandleX 类都有一个 isOk 的静态方法, 接收淘宝接口返回值, 返回true或错误代码名
 * 错误代码名见 \config\code.php
 */
class TbResultHandle3 {

    public function isOk ($type, $raw) {

        $result = null;
        switch ($type) {
            case 'xiangqing.wangpu.taobao.com':
                $result = Handle1::isOk($raw);
                break;
            case 'tadget.taobao.com':
                $result = Handle2::isOk($raw);
                break;
            case 'stream-upload.taobao.com':
                $result = Handle3::isOk($raw);
                break;
            case 'stream.taobao.com':
                $result = Handle4::isOk($raw);
                break;
            default:
                $result = true;
        }

        return $result;
    }
}


/**
 * 适用的返回格式:
    [
        'code' => 0,
        'msg' => ,
        'error' => false,
        'data' => []
    ]
 * 
 * 
 * 通过code和error判断是否成功, 通过code和message判断错误类型
 * 
 * 适用接口:
 * https://xiangqing.wangpu.taobao.com/item/ajax/ItemList.do
 */
class Handle1 {


    // 是否登录
    private static function isLoginOk ($code, $msg) {

        preg_match('/请先登录/', $msg, $p);
        return ($code === 1 && !empty($p))
            ? 'TB_UN_LOGIN'
            : true;
    }


    // 是否图片空间满了
    private static function isPicSpaceFull ($code, $msg) {

        preg_match('/容量不足，请登录图片空间（tu.taobao.com）清理图片或订购存储功能包/', $msg, $p);
        return ($code === 1000 && !empty($p))
            ? 'TB_PIC_SPACE_FULL'
            : true;
    }


    // 入口
    public static function isOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);
        } catch (\Throwable $th) {
            
            $input = null;
        }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        
        // 通过这3个字段判断是否正常, 没有该字段, 无法判断
        if (
            !array_key_exists('error', $input) ||
            !array_key_exists('code', $input) ||
            !array_key_exists('msg', $input)
        ) return 'SYSTEM_ERROR';


        // 接口返回正常
        if ($input['error'] === false && $input['code'] === 0) return true;


        // ---接口返回异常
        $code = $input['code'];
        $msg = $input['msg'];

        // 挨个调用异常处理方法, 看是什么异常
        $isOk = self::isLoginOk($code, $msg);
        if ($isOk !== true) return $isOk;

        $isOk = self::isPicSpaceFull($code, $msg);
        if ($isOk !== true) return $isOk;
        
        // 默认返回“系统错误”的异常
        return 'SYSTEM_ERROR';
    }
}


/**
 * 适用的返回格式:
    [
        'success' => true,
        'message' => null,
        'module' => [xxx],
        'crsToken' => '',
    ]
 * 
 * 
 * 通过success判断是否成功, 通过message判断错误类型
 * 
 * 适用接口:
 * https://tadget.taobao.com/redaction/redaction/json.json
 */
class Handle2 {

    // 是否登录失效
    private static function isLoginOk ($message) {

        preg_match('/用户登陆已过期/', $message, $p);
        if (!empty($p)) return 'TB_UN_LOGIN';

        return true;
    }


    // 父目录Id不存在
    private static function isParentDirIdErr ($message) {

        preg_match('/父目录Id不存在/', $message, $p);
        if (!empty($p)) return 'TB_UN_LOGIN';

        return true;
    }


    // 文件夹命名重复
    private static function isDirNameRepeat ($message) {

        preg_match('/在同一个目录下有相同的名字/', $message, $p);
        if (!empty($p)) return 'DIR_NAME_REPEAT_ERR';

        return true;
    }


    // 入口
    public static function isOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);
        } catch (\Throwable $th) {
            
            $input = null;
        }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        
        // 通过success判断接口是否正常返回, 没有该字段, 无法判断
        if (!array_key_exists('success', $input)) return 'SYSTEM_ERROR';


        // 接口返回正常
        if ($input['success']) return true;


        // ---接口返回异常
        // 通过 message 判断接口是什么异常, 没有这个字段无法判断
        if (!array_key_exists('message', $input)) return 'SYSTEM_ERROR';

        $message = $input['message'];

        // 挨个调用异常处理方法, 看是什么异常
        $isOk = self::isLoginOk($message);
        if ($isOk !== true) return $isOk;

        $isOk = self::isParentDirIdErr($message);
        if ($isOk !== true) return $isOk;

        $isOk = self::isDirNameRepeat($message);
        if ($isOk !== true) return $isOk;
        
        // 默认返回“系统错误”的异常
        return 'SYSTEM_ERROR';
    }
}


/**
 * 适用的返回格式:
    [
        'errorCode' => 'xxxx',
        'hasNext' => xxxx,
        'message' => 'xxxx',
        'status' => xxxx,
        'success' => xxxx,
        'total' => xxxx,
    ]
 * 
 * 通过 success 判断是否成功, 通过message和errorCode判断错误类型
 * 
 * 适用接口:
 * https://stream-upload.taobao.com/api/upload.api
 */
class Handle3 {


    // 是否为文件名异常
    private static function isFilenameOk ($errorCode, $message) {

        preg_match('/文件名称包含特殊字符/', $message, $p);
        if (
            $errorCode === 'FILE_NAME_CONTAIN_SPECIAL_CHARACTER' &&
            !empty($p)
        ) return 'UPLOAD_FILENAME_ERR';

        return true;
    }


    // 入口
    public static function isOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);
        } catch (\Throwable $th) {
            
            $input = null;
        }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        
        // 通过success判断接口是否正常返回, 没有该字段, 无法判断
        if (!array_key_exists('success', $input)) return 'SYSTEM_ERROR';


        // 接口返回正常
        if ($input['success']) return true;


        // ---接口返回异常

        // 通过 errorCode 和 message 判断接口是什么异常, 没有这些字段无法判断
        if (
            !array_key_exists('errorCode', $input) ||
            !array_key_exists('message', $input)
        ) return 'SYSTEM_ERROR';


        $errorCode = $input['errorCode'];
        $message = $input['message'];

        // 挨个调用异常处理方法, 看是什么异常
        $isOk = self::isFilenameOk($errorCode, $message);
        if ($isOk !== true) return $isOk;


        // 默认返回“系统错误”的异常
        return 'SYSTEM_ERROR';
    }
}


/**
 * 适用的返回格式:
    [
        'object' => [xxx],
        'total' => 21,
        'hasNext' => true,
        'success' => true,
        'status' => 0
    ]
 * 
 * 通过 success 判断是否成功, 通过 status 判断错误类型
 * 
 * 适用接口:
 * https://stream.taobao.com/api/get_files.api
 */
class Handle4 {


    // 入口
    public static function isOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);
        } catch (\Throwable $th) {
            
            $input = null;
        }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        
        // 通过success判断接口是否正常返回, 没有该字段, 无法判断
        if (!array_key_exists('success', $input)) return 'SYSTEM_ERROR';


        // 接口返回正常
        if ($input['success']) return true;

        
        // ---接口返回异常

        // 通过 status 判断接口是什么异常, 没有这些字段无法判断
        if (!array_key_exists('status', $input)) return 'SYSTEM_ERROR';
        
        $status = $input['status'];

        // 挨个调用异常处理方法, 看是什么异常


        // 默认返回“系统错误”异常
        return 'SYSTEM_ERROR';
    }
}