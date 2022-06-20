<?php
namespace app\http\worker;

use app\model\WebSocketSessionModel;
use app\extend\RecordError;

class WorkerOnClose {

    public function handle ($connection) {

        $worker_id = $connection->worker->id;   // 子进程id
        $connection_id = $connection->id;       // 连接id
        $user_key = WebSocketSessionModel::buildKey($worker_id, $connection_id);
        
        WebSocketSessionModel::where('key', $user_key)->delete();

        RecordError::handle(__CLASS__, "worker_id: $worker_id; connection_id: $connection_id;", '用户登出');
    }
}