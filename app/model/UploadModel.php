<?php
namespace app\model;

use think\Model;


/**
 * 此表存放上传到千绘服务器的文件
 */
class UploadModel extends Model {
    
    // 表名
    protected $table = 'uploads';

    // 模型字段
    protected $schema = [
        // 自增id
        'id' => 'int',
        // 原文件名, 例如  aaa.jpg
        'origin_name' => 'varchar',
        // 文件名后缀, 例如  jpg
        'ext' => 'varchar',
        // 文件md5
        'md5' => 'varchar',
        // 文件本地地址
        'local_path' => 'text',
        // 文件字节大小
        'size' => 'bigint',
        // 文件创建时间
        'create_time' => 'bigint',
    ];
}
