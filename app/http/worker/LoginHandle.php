<?php
namespace app\http\worker;

use app\extend\Util;
use app\model\OldUserModel;
use app\model\WebSocketSessionModel;
use app\extend\RecordError;

class LoginHandle {

    public function exec ($data, $connection) {

        $uid = $data['uid'];
        $user = OldUserModel::where('user_id', $uid)->find();

        // 用户是否存在
        if ($user == null) return Util::formatResult([], false, 'USER_UNDEFINED');


        $worker_id = $connection->worker->id;   // 子进程id
        $connection_id = $connection->id;       // 连接id
        $user_key = WebSocketSessionModel::buildKey($worker_id, $connection_id);

        // 该用户是否已登录
        $webSocketSession = WebSocketSessionModel::where('key', $user_key)->find();

        // 该用户已经登录, 不允许重复登录
        if ($webSocketSession) return Util::formatResult([], false, 'LOGIN_REPEAT');

        // 记录用户的登录状态
        WebSocketSessionModel::create([
            'key' => $user_key,
            'uid' => $uid,
            'create_time' => time(),
        ]);

        return Util::formatResult([], true, 'LOGIN_SUCC');
    }
}