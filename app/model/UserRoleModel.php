<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class UserRoleModel extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'user_role';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'id' => 'int',
        'pid' => 'int',
        'is_height3w' => 'enum',        // 高度3万
        'is_gif' => 'enum',     // 动图
        'is_text' => 'enum',    // 文本
        'is_image' => 'enum',   // 原图
        'is_wimage' => 'enum',  // 旺铺原图
        'is_video' => 'enum',   // 视频
        'is_hvideo' => 'enum',  // 竖版视频
        'is_hdv' => 'enum',     // 超高清
        'tids' => 'varchar',    // 模板ID
        'h3_time' => 'char',    // 高度到期时间
        'g_time' => 'char',     // 动图到期时间
        't_time' => 'char',     // 文本到期时间
        'i_time' => 'char',     // 原图到期时间
        'wi_time' => 'char',    // 旺铺原图到期时间
        'v_time' => 'char',     // 视频到期时间
        'hv_time' => 'char',    // 竖版到期时间
        'hdv_time' => 'char',   // 超高清到期时间
    ];
}
