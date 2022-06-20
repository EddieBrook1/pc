<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class OldQhLoginToken extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'qh_login_token';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'id' => 'int',
        'uid' => 'int',
        'token' => 'varchar',
        'token_update_time' => 'bigint'
    ];
}
