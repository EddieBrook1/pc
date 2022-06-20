<?php
namespace app\http\worker;

use app\http\worker\listeners\SyncMainJobListener;

// worker子进程启动时的回调处理类
class WorkerOnStart {

    public function handle ($worker) {

        // 推送最近若干条同步任务消息
        (new SyncMainJobListener)->handle($worker);
    }
}