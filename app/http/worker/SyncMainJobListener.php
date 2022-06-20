<?php
namespace app\http\worker;

use Workerman\Lib\Timer;
use app\model\SyncMainJobModel;
use app\extend\Util;
use app\model\WebSocketSessionModel;
use app\extend\RecordError;

// 侦听同步进度, 推送给对应已登录的用户
class SyncMainJobListener {

    public function handle ($worker) {

        // 5秒侦听一次
        Timer::add(2, function () use ($worker) {
            // 捕捉错误, 定时器内部代码出错不会抛出
            try {


                // 取出所有未结束的任务
                $main_job_arr = SyncMainJobModel::where('done_time', 0)->select()->toArray();

                // 整理数据, 键名为uid, 键值为json字符串组成的数组, 内容是与它相关的数据
                $result = [];
                foreach ($main_job_arr as $item) {

                    $uid = $item['uid'];
                    $code_name = Util::getCodeNameByCode($item['status_code']);
                    $result_item = [
                        'goods_id' => $item['goods_id'],
                        'template_id' => $item['template_id'],
                        'progress' => $item['progress'],
                    ];

                    // 如果没有初始化一个
                    if (!array_key_exists($uid, $result)) $result[$uid] = [];
                    
                    array_push(
                        $result[$uid],
                        json_encode(Util::formatResult($result_item, true, $code_name), 320)
                    );
                }


                // 如果没有同步数据跳过
                if (count($result) == 0) return;


                // 取出目前已登录的用户
                $logged_users = WebSocketSessionModel::select()->toArray();
                // 取出当前子进程所有连接
                $connections = $worker->connections;
                // 子进程id
                $worker_id = $worker->id;

                // 一一匹配
                foreach ($logged_users as $user) {
                    foreach ($connections as $connection) {

                        $user_key = WebSocketSessionModel::buildKey($worker_id, $connection->id);                    
                        if ($user_key == $user['key']) {

                            // 遍历推送消息
                            $msg_arr = $result[$user['uid']];
                            foreach ($msg_arr as $msg) {

                                $connection->send($msg);
                            }
                            break;
                        }
                    }
                }


            } catch (\Throwable $th) {
               
                RecordError::handle(__CLASS__, $th, 'workman定时器报错');
            }
        });
    }
}