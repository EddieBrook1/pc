<?php
namespace app\model;

use think\Model;


/**
 * 此表存放上传到千绘服务器的文件
 */
class SyncMainJobModel extends Model {


    public const TABLE_NAME = 'sync_main_jobs';

    // 表名
    protected $table = 'sync_main_jobs';

    // 模型字段
    protected $schema = [
        // 自增id
        'id' => 'int',
        // 用户id, 千绘的uid
        'uid' => 'varchar',
        // 模版id
        'template_id' => 'int',
        // 淘宝商品id
        'goods_id' => 'varchar',
        // 编辑器数据
        'editor_data' => 'longtext',
        // 同步端类型, 0电脑端，1手机端，2电脑端和手机端
        'client_type' => 'int',
        // 图片数据
        'resource_object' => 'longtext',
        // 当前任务状态码
        'status_code' => 'int',
        // 任务进度，如 1, 15, 99, 100
        'progress' => 'int',
        // 文本编辑格式的同步数据
        'template_content_text' => 'longtext',
        // 任务创建时间
        'create_time' => 'bigint',
        // 任务终止时间
        'done_time' => 'bigint',
    ];
}
