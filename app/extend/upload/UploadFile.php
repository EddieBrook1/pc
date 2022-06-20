<?php
namespace app\extend\upload;

use think\facade\Filesystem;
use app\model\UploadModel;
use app\extend\Util;

/**
 * 上传文件类
 * 依赖:
 * 1. 前端的 fineuploader上传插件[https://docs.fineuploader.com]
 * 2. fineuploader插件按分片传输, 无论文件大小
 * 3. 数据表 uploads 表, 即 UploadModel 模型
 * 
 * 逻辑:
 * 1. 接收分片, 暂存分片到uuid命名的文件夹里, 如果没有则创建, uuid是fineuploader插件生成的
 * 2. 判断全部分片是否上传完成, 根据uuid文件夹里已有分片数和qqtotalparts比较
 * 3. 对合并后的文件生成md5, 对比数据库是否有该记录, 如果有返回该记录并删除分片文件夹即uuid文件夹
 * 4. 如果数据库没有记录则记录下来, 把合并后的文件移入持久化文件夹 storage/upload 文件夹, 然后删除uuid文件夹, 然后返回数据库记录
 * 5. 数据库中 filename是 qqfilename 的值, md5是合并后文件的md5
 */
class UploadFile {

    // 允许上传的文件类型
    private const ACCEPT_MIME = [
        'image/jpeg',
        'image/png',
        'video/mp4'
    ];
    // 分片目录
    private $CHUNK_DIR;
    // 合并之后且类型允许的文件所在目录
    private $UPLOAD_ROOT;


    public function __construct () {

        // 获取服务器缓存目录
        $storage_dir = app()->getRuntimePath() . 'storage/';
        
        // 分片目录
        $this->CHUNK_DIR = $storage_dir . 'chunk';
        // 持久目录
        $this->UPLOAD_ROOT = $storage_dir . 'upload';

        // storage可能不存在, 不存在先创建该目录
        if (!is_dir($storage_dir)) \mkdir($storage_dir);

        // 分片目录不存在则创建
        if (!is_dir($this->CHUNK_DIR)) \mkdir($this->CHUNK_DIR);

        // 上传根目录不存在则创建
        if (!is_dir($this->UPLOAD_ROOT)) \mkdir($this->UPLOAD_ROOT);
    }

    
    /**
     * 计算文件夹下有几个文件, 把$path下的所有文件都视作文件
     *
     * @param [type] $path  文件夹路径
     * @return void
     */
    private static function fileNums ($path) {

        $names = array_map(function ($name) {

            if ($name != '.' && $name != '..') return $name;
        }, \scandir($path));


        $num = 0;
        $names = \scandir($path);
        foreach ($names as $val) {

            if ($val !== '.' && $val !== '..') {

                $num += 1;
            }
        }

        return $num;
    }


    // 合并分片
    private static function merge ($chunk_dir, $file_path) {

        $names = \scandir($chunk_dir);
        $names2 = [];
        foreach ($names as $val) {
            
            if ($val !== '.' && $val !== '..') {

                array_push($names2, $val);
            }
        }
        sort($names2);

        foreach ($names2 as $chunk_name) {

            // 当前分片名称
            $chunk_path = $chunk_dir . "/$chunk_name";

            // 追加数据
            \file_put_contents(
                $file_path,
                \file_get_contents($chunk_path),
                FILE_APPEND
            );
        }
    }


    // 删除分片文件夹和里面所有文件
    private static function delChunkDir ($chunk_dir) {

        $names = scandir($chunk_dir);
        foreach ($names as $val) {

            if ($val == '.' || $val == '..') continue;
            $file_path = $chunk_dir . "/$val";
            \unlink($file_path);
        }

        \rmdir($chunk_dir);
    }


    // 分片上传成功的返回值
    private static function chunkSuccRes () {
        
        $result = Util::formatResult([ 'id' => null ], true, 'UPLOAD_SUCC');
        $result['success'] = true;
        $result['done'] = false;
        return $result;
    }


    // 整个文件上传成功的返回值
    private static function completeRes ($id) {
        
        $result = Util::formatResult([ 'id' => $id ], true, 'UPLOAD_SUCC');
        $result['success'] = true;
        $result['done'] = true;
        return $result;
    }


    // 异常返回值
    private static function errorRes ($err_name) {
            
        $result = Util::formatResult([ 'id' => null ], false, $err_name);
        $result['success'] = false;
        $result['done'] = false;
        return $result;
    }


    /**
     * 入口
     *
     * @param [Array] $input 形如
        [
            'qquuid' => 'xxx',
            'qqpartindex' => 1
            'qqtotalparts' => 1
            'real_filename' => 'xxx'
        ]
     * 
     * @return Array Util::formatResult 的返回值
     * 
     * 上传未全部完成时的返回值
        [
            'error' => false,
            'code' => 1019,
            'msg' => '上传成功',
            'data' => [ 'id' => null ],
            'done' => false,
            'success' => true
        ]

     *
     *  上传全部完成时的返回值
        [
            'error' => false,
            'code' => 1019,
            'msg' => '上传成功',
            'data' => [ 'id' => 15 ],
            'done' => true,
            'success' => true
        ]
     * 
     * 文件类型不允许时的返回值
        [
            'error' => true,
            'code' => 1021,
            'msg' => '上传失败, 文件类型不允许',
            'data' => [ 'id' => null ],
            'done' => false,
            'success' => false
        ]
     * 
     * 其中 done = false 表示这个文件还没上传完成
     * success = true 是给前端插件看的, 表示分片上传成功 success = false 表示分片上传失败
     * 文件上传成功后 data['id'] 才会有值, 否则为null
     * 
     */
    public function upload ($input) {

        $qqfile         = 'qqfile';
        $qquuid         = $input['qquuid'];
        $qqpartindex    = $input['qqpartindex'];
        $qqtotalparts   = $input['qqtotalparts'];
        $real_filename  = $input['real_filename'];


        /* ------------------------- 分片上传 ------------------------- */
        // 创建分片文件夹
        $chunk_dir = $this->CHUNK_DIR . "/$qquuid";
        if (!\is_dir($chunk_dir)) {
            \mkdir($chunk_dir);
        }

        // 获取文件临时路径
        $tmp_name = $_FILES[$qqfile]['tmp_name'];

        // 新的路径
        $chunk_dist_name = $chunk_dir . "/$qqpartindex";

        // 移动到分片目录
        \move_uploaded_file($tmp_name, $chunk_dist_name);

        // 分片上传成功
        if (self::fileNums($chunk_dir) != $qqtotalparts) {

            return self::chunkSuccRes();
        }
        /* ------------------------- 分片上传 end ------------------------- */




        /* -------------------- 最后一个分片执行这串逻辑 -------------------- */
        // 分片合并后的路径
        $merged_path = $chunk_dir . "/$real_filename";
        // 合并分片
        self::merge($chunk_dir, $merged_path);


        // 获取合并后文件的mime
        $mime = \mime_content_type($merged_path);
        // 类型不允许, 删除分片和该文件的分片目录
        if (!in_array($mime, self::ACCEPT_MIME)) {
            
            self::delChunkDir($chunk_dir);
            return self::errorRes('UPLOAD_TYPE_ERR');
        }


        // 计算文件md5
        $file_md5 = \md5_file($merged_path);
        // 文件后缀
        $pathinfo = pathinfo($merged_path);
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        // 判断数据库是否已存在该文件
        $uploadModel = UploadModel::where('md5', $file_md5)->find();

        // 已存在返回该记录的id, 删除分片
        if ($uploadModel) {

            self::delChunkDir($chunk_dir);
            return self::completeRes($uploadModel->id);
        } else {
            // 不存在把图片移动到持久目录里, 记录数据库

            $time = time();
            $uploaded_name = $ext == ''
                ? "$file_md5-$time"
                : "$file_md5-$time.$ext";
            $uploaded_path = $this->UPLOAD_ROOT . "/$uploaded_name";
            \rename($merged_path, $uploaded_path);
            self::delChunkDir($chunk_dir);


            // 记录数据库
            $newlyUploadModel = UploadModel::create([
                'origin_name' => $real_filename,
                'ext' => $ext,
                'md5' => $file_md5,
                'local_path' => $uploaded_path,
                'size' => filesize($uploaded_path),
                'create_time' => $time,
            ]);

            return self::completeRes($newlyUploadModel->id);
        }
    }
}