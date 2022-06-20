<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class OldXqGroupModule extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'xq_groups';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'id'            => 'int',       // 
        'groups'        => 'mediumtext',   // 模块属性
        'top_groups'    => 'mediumtext',   // 临时模版属性
        'tmp_id'        => 'varchar',      // 模版id
        'other_data'    => 'varchar',      // 模板其他数据（例如：千牛、阿里）
    ];
}
