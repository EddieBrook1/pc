<?php
namespace app\http;

use Workerman\Lib\Timer;
use think\worker\Server;
use think\facade\Log;


class Worker extends Server {

    protected $protocol       = 'websocket';    // [workman]使用websocket协议
    protected $host           = '0.0.0.0';      // [workman]监听地址
    protected $port           = 39003;          // [workman]监听端口
    protected $count          = 4;              // [workman]要启动多少个子进程, 即调用多少次 new Worker
    
    // [workman][官网原文]此属性为全局静态属性，用来设置WorkerMan进程的pid文件路径
    // 因为使用宝塔启动守护进程, 宝塔规定所有项目都用www身份来启动, 但默认pidFile是写到 vendor 文件夹的, www身份无法写入
    // 所以要在构造函数内设置这个值到www有权限写入的文件夹里。这里把它设置到runtime文件夹里
    protected $pidFile        = '';


    // 心跳超时时间
    private const HEARTBEAT_TIME = 55;

    // 已连接的客户端
    private $client_list = [];

    // 前端发来数据允许的type类型
    private $accept_type = [
        'login', // 登录
    ];

    public function __construct () {
        
        parent::__construct();

        $runtime_path = app()->getRuntimePath();
        $workman_path = "$runtime_path/workman";
        $pidfile_path = "$workman_path/pidfile.pid";
        if (!is_dir($workman_path)) mkdir($workman_path);
        if (!is_file($pidfile_path)) touch($pidfile_path);

        $this->pidFile = $pidfile_path;
    }


    /**
     * 判断前端发来的数据是否可用
     * 要求 $data 是一个json字符串, 且转成数组后形如:
        [
            'type' => 'login',
            'data' => [xxxxx]
        ]
     *
     * @return boolean
     */
    private function isDataOk ($raw) {

        $data = json_decode($raw, true);

        // 无法转换成数组, 返回
        if (!$data) return false;

        // 格式不正确, 返回
        if (
            !isset($data['type']) ||
            !isset($data['data'])
        ) return false;

        // type不允许, 返回
        if (!in_array($data['type'], $this->accept_type)) return false;

        // 正常, 返回数组
        return $data;
    }

    
    // 计算用户唯一id, 子进程id + 连接id
    private function getClientId ($connection) {

        $client_id = $connection->worker->id . $connection->id;
        return "k_$client_id";
    }


    // 记录连接的客户端
    private function addClient ($connection, $data) {

        // 没有uid选项不做其他操作
        if (!isset($data['uid'])) return;

        $uid = $data['uid'];
        $client_id = $this->getClientId($connection);

        if (!isset($this->client_list[$client_id])) {

            $this->client_list[$client_id] = [
                'uid' => $uid,
                'connection' => $connection,
            ];
        }
    }


    // 移除连接的客户端
    private function removeClient ($connection) {

        $client_id = $this->getClientId($connection);
        unset($this->client_list[$client_id]);
    }


    /**
     * 格式化日志记录
     *
     * @param string $msg   提示语
     * @param string $type  错误类型, 目前可选 info、error, 根据不同的类型加上对应的前缀
     * @return void
     */
    private function recordInLog ($msg, $type = 'info') {

        $prefix = '';
        switch ($type) {
            case 'info':
                $prefix = '[workman提示: ]';
                break;
            case 'error':
                $prefix = '[workman报错: ]';
        }

        $msg = strval($msg);
        Log::record("$prefix $msg");
    }


    // [workman]workman子进程启动后调用, $count为多少就调用多少次
    public function onWorkerStart  ($worker) {

        // 每隔5秒更新一次同步进度, 并推送消息给对应客户端
        // Timer::add(5, function () use ($worker) {});

        // 每一段时间检查心跳, 如果心跳超时任务链接已断开, 手动执行close方法, 触发 onClose 回调
        Timer::add(10, function () use ($worker) {

            $client_ids = implode(', ', array_keys($this->client_list));
            $uids = implode(', ', array_column(array_values($this->client_list), 'uid'));
            $this->recordInLog("已登录用户: ($client_ids), 对应的uid: ($uids)");

            $time_now = time();
            foreach($worker->connections as $connection) {

                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastMessageTime > self::HEARTBEAT_TIME) {

                    $connection->close();
                }
            }
        });

    }


    // [workman]onWorkerReload
    public function onWorkerReload ($worker) {

    }


    // [workman]onConnect
    public function onConnect ($connection) {

    }


    // [workman]当收到前端发来的消息, 要回复什么, 该回复是固定的, 没办法动态返回值
    public function onMessage ($connection, $raw) {
        
        // 判断数据是否可用; 捕获错误方便调试
        try {
            
            $data = $this->isDataOk($raw);
            if ($data) {

                // 根据type类型执行不同的逻辑
                switch ($data['type']) {
                    case 'login':
                        $this->addClient($connection, $data['data']);
                        break;
                }
            }
        } catch (\Throwable $th) {

            $this->recordInLog($th->getMessage(), 'error');
        }

        // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
        $connection->lastMessageTime = time();

        // 应答一个时间戳
        $connection->send(time());
    }


    // [workman]onClose
    public function onClose ($connection) {

        $this->removeClient($connection);

        $client_id = $this->getClientId($connection);
        $this->recordInLog("$client_id 用户已断开");
    }


    // [workman]onError
    public function onError ($connection, $code, $msg) {

        $this->recordInLog("code: $msg; msg: $msg", 'error');
        echo "error [ $code ] $msg\n";
    }
}