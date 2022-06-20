<?php
namespace app\http;

use Workerman\Lib\Timer;
use think\worker\Server;
use app\http\worker\WorkerOnStart;
use app\http\worker\WorkerOnMessage;
use app\http\worker\WorkerOnClose;
use app\extend\RecordError;

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


    public function __construct () {
        
        parent::__construct();

        $runtime_path = app()->getRuntimePath();
        $workman_path = "$runtime_path/workman";
        $pidfile_path = "$workman_path/pidfile.pid";
        if (!is_dir($workman_path)) mkdir($workman_path);
        if (!is_file($pidfile_path)) touch($pidfile_path);

        $this->pidFile = $pidfile_path;
    }


    // [workman]workman子进程启动后调用, $count为多少就调用多少次
    public function onWorkerStart  ($worker) {

        // 每一段时间检查心跳, 如果心跳超时任务链接已断开, 手动执行close方法, 触发 onClose 回调
        Timer::add(10, function () use ($worker) {

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

        try {

            (new WorkerOnStart)->handle($worker);
        } catch (\Throwable $th) {

            RecordError::handle(__CLASS__, $th, 'WorkerOnStart报错');
        }
    }


    // [workman]onWorkerReload
    public function onWorkerReload ($worker) {

    }


    // [workman]onConnect
    public function onConnect ($connection) {

    }


    // [workman]当收到前端发来的消息, 要回复什么, 该回复是固定的, 没办法动态返回值
    public function onMessage ($connection, $raw) {

        try {

            (new WorkerOnMessage)->handle($connection, $raw);
        } catch (\Throwable $th) {

            RecordError::handle(__CLASS__, $th, 'WorkerOnMessage报错');
        }


        // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
        $connection->lastMessageTime = time();
    }


    // [workman]onClose
    public function onClose ($connection) {

        try {

            (new WorkerOnClose)->handle($connection);
        } catch (\Throwable $th) {

            RecordError::handle(__CLASS__, $th, 'WorkerOnClose报错');
        }
    }


    // [workman]onError
    public function onError ($connection, $code, $msg) {

        RecordError::handle(__CLASS__, $th, 'onError报错');
    }
}