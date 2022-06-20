<?php
namespace app\model;

use think\Model;


/**
 * 此表存储用户未装插件情况即得不到商品id时编辑后的数据
 * 通过 模版id + 用户id 定位一条记录
 */
class OldXqGroupsUserModel extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'xq_groups_user';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'id'            => 'int',
        'groups'        => 'mediumtext',     // 用户模板数据
        'tmp_id'        => 'varchar',        // 模板id
        'user_id'       => 'varchar',        // 用户id
        'puser_id'      => 'varchar',        // 拼多多用户id
        'tmpstatus'     => 'enum',           // 模板的应用状态:0,已冻结 1，正在使用
        'gif'           => 'text',           // 动图数据
        'video'         => 'text',           // 视频数据
        'hotspot'       => 'text',           // 热区数据
        'create_time'   => 'bigint',           // 创建时间
        'update_time'   => 'bigint',           // 更新时间
        'delete_time'   => 'bigint',           // 软删除时间
    ];
}
