<?php
namespace app\model;

use think\Model;


/**
 * 此表存已抠图过的原图和结果图
 */
class KoutuModel extends Model {
    
    // 表名
    protected $table = 'koutus';

    // 模型字段
    protected $schema = [
        'id' => 'int',
        // 原图网络链接
        'origin_url' => 'text',
        // 结果图网络链接
        'after_url' => 'text',
        // 原图本地链接
        'local_origin_url' => 'text',
        // 结果图本地链接
        'local_after_url' => 'text',
        // 文件md5
        'md5' => 'varchar'
    ];
}
