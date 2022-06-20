<?php
namespace app\http\worker\listeners;

use Workerman\Lib\Timer;
use app\model\SyncMainJobModel;
use app\extend\Util;
use app\model\WebSocketSessionModel;
use app\http\ErrorWrap;
use think\facade\Db;
use app\extend\RecordError;

// 侦听同步进度, 推送给对应已登录的用户
class SyncMainJobListener {

    private const JOB_LIMIT = 10;


    // 取出任务
    public function getJobs ($all_uid) {

        // 表名
        $table_name = SyncMainJobModel::TABLE_NAME;
        // 要查询的字段
        $field = 'id,uid,template_id,goods_id,status_code,progress,create_time,done_time';

        // 实例化Db类
        $dbSQL = Db::table($table_name)
            ->field($field)
            ->where('uid', $all_uid[0])
            ->order('create_time', 'desc')
            ->limit(self::JOB_LIMIT);

        // 去掉已经用过的uid
        array_shift($all_uid);

        // 生成 union 的原生SQL
        $sql_arr = [];
        foreach ($all_uid as $uid_item) {

            array_push(
                $sql_arr,
                "SELECT $field FROM $table_name
                    WHERE `uid` = $uid_item
                    ORDER BY `create_time` DESC
                    LIMIT " . self::JOB_LIMIT
            );
        }


        // 如果有多个uid, 需用union拼装成一条SQL语句一次性查出
        if (count($sql_arr) > 0) {
            
            foreach ($sql_arr as $sql) {

                $dbSQL = $dbSQL->union($sql);
            }
        }


        // 查询
        return $dbSQL->select()->toArray();
    }


    // 给 $jobs 追加状态码对应的中文
    private function appendStatusLabel ($jobs) {

        foreach ($jobs as &$item) {

            $code = $item['status_code'];
            $code_name = Util::getCodeNameByCode($code);
            $code_data = config("code.$code_name");

            $item['status_label'] = $code_data['msg'];
        }

        return $jobs;
    }


    // 按用户区分job
    private function groupingJobs ($all_uid, $jobs) {

        $result = [];

        // 一一比对
        foreach ($all_uid as $uid) {
            foreach ($jobs as $job) {

                if ($job['uid'] != $uid) continue;
                if (!isset($result[$uid])) $result[$uid] = [];

                array_push($result[$uid], $job);
            }
        }

        return $result;
    }


    // 给$logged_users追加它们对应的workman连接, 后面方便循环
    private function appendConnectionToUser ($worker, $logged_users) {

        $worker_id = $worker->id;
        $connections = $worker->connections;

        // 一一比对并追加
        foreach ($logged_users as &$user) {
            foreach ($connections as $connection) {

                $key = WebSocketSessionModel::buildKey($worker_id, $connection->id);
                if ($user['key'] == $key) {
                    
                    $user['connection'] = $connection;
                }
            }
        }

        return $logged_users;
    }


    // 入口
    private function _handle ($worker) {

        // 取出已登录的用户
        $logged_users = WebSocketSessionModel::select()->toArray();

        // 没有用户登录, 跳过
        if (count($logged_users) == 0) return;

        // 取出所有用户的uid
        $all_uid = array_column($logged_users, 'uid');  // 全部的uid
        $all_uid = array_unique($all_uid);              // 去重

        // 每个用户取出若干条记录
        $jobs = $this->getJobs($all_uid);

        // 追加 status_code 对应的中文解释
        $jobs = $this->appendStatusLabel($jobs);

        // 把数组按uid分组
        $grouped = $this->groupingJobs($all_uid, $jobs);

        // 给$logged_users追加它们对应的workman连接, 后面方便循环
        $logged_users = $this->appendConnectionToUser($worker, $logged_users);

        // 推送消息
        foreach ($logged_users as $user) {

            $uid = $user['uid'];
            $connection = $user['connection'];

            // 没有消息跳过
            if (!isset($grouped[$uid])) continue;

            // 包装好消息
            $message_raw = $grouped[$uid];
            $message = Util::formatResult([
                'history' => $message_raw,
                'current_time' => time(),
            ], true, 'SYNC_HISTORY');

            // 推送
            $connection->send(json_encode($message, 320));
        }
    }


    // 入口
    public function handle ($worker) {

        Timer::add(2, ErrorWrap::handle(__CLASS__, function () use ($worker) {

            $this->_handle($worker);
        }));
    }
}