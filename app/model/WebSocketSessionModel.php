<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class WebSocketSessionModel extends Model {

    // 表名
    protected $table = 'web_socket_sessions';

    // 模型字段
    protected $schema = [
        'id' => 'int',
        'key' => 'varchar',
        'uid' => 'int',
        'create_time' => 'bigint',
    ];


    /**
     * 生成key字段
     *
     * @param [Number] $worker_id       workman子进程id
     * @param [Number] $connection_id   子进程中某条连接的id
     * @return String key
     */
    public static function buildKey ($worker_id, $connection_id) {

        return 'k_' . $worker_id . '_' . $connection_id;
    }
}
