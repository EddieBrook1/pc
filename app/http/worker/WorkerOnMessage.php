<?php
namespace app\http\worker;

use think\facade\Log;
use app\http\worker\MessageValidator;
use app\extend\Util;
use app\extend\RecordError;

use app\http\worker\handles\LoginHandle;
use app\http\worker\handles\HeartbeatHandle;

// 收到前端推送消息时的回调处理类
class WorkerOnMessage {

    // 提取出input中的msg_id, 如果没有默认 -1
    private function getMsgId ($input) {

        $msg_id = null;
        if (is_array($input)) {

            $msg_id = isset($input['msg_id']) ? $input['msg_id'] : -1;
        } else {

            $msg_id = -1;
        }

        return $msg_id;
    }


    public function handle ($connection, $raw) {

        $input = json_decode($raw, true);
        
        // 获取msg_id
        $msg_id = $this->getMsgId($input);

        // 验证入参, 错误返回
        $isRawOk = (new MessageValidator)->handle($input);
        if (!$isRawOk) {

            $result_payload = [ 'msg_id' => $msg_id ];
            $result = json_encode(Util::formatResult($result_payload, false, 'PARAM_ERR'));
            $connection->send($result);
            return;
        }

        
        // 根据handle调用对应处理类
        $handle = $input['handle'];     // 处理类的名称
        $input_data = $input['data'];   // 入参的数据
        $handle_result = null;          // 处理类的返回值

        switch ($handle) {
            // 登录
            case 'login':
                $handle_result = (new LoginHandle)->exec($input_data, $connection);
                break;
            // 心跳
            case 'heartbeat':
                $handle_result = (new HeartbeatHandle)->exec($input_data, $connection);
        }


        // 添加msg_id, 返回
        $handle_result['data']['msg_id'] = $msg_id;
        $connection->send(json_encode($handle_result, 320));
    }
}