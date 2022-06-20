<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class OldDetailTemplateModel extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'detail_template';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'id'            => 'int',
        'template'      => 'mediumtext',     // 用户模板数据
        'uid'           => 'int',            // 用户id
        'tid'           => 'int',            // 模板id
        'gid'           => 'varchar',        // 商品id
        'hotspot'       => 'mediumtext',     // 热区数据
        'gif'           => 'mediumtext',     // 动图数据
        'video'         => 'mediumtext',     // 视频数据
        'create_time'   => 'char',           // 创建时间
        'update_time'   => 'char',           // 更新时间
        'delete_time'   => 'char',           // 软删除时间
    ];
}
