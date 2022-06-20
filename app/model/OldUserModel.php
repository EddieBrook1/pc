<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class OldUserModel extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'user';

    // 模型字段
    protected $schema = [
        'user_id' => 'int',
        'userName' => 'varchar',
        'password' => 'varchar',
        'mobilePhone' => 'char',
        'code' => 'enum',
        'group_id' => 'smallint',
        'ip' => 'varchar',
        'time' => 'varchar',
        'addip' => 'varchar',
        'addtime' => 'varchar',
        'pace_id' => 'int',
        'open_uid' => 'varchar',
        'Email' => 'varchar',
        'sub_uid' => 'varchar',
        'integrate' => 'varchar',
        'access_token' => 'varchar',
        'number_uid' => 'varchar',
        'avatar' => 'text',
        'cookie' => 'text',
        'sso_token' => 'text',
        'sso_token_time' => 'bigint',
    ];
}
