<?php
namespace app\listener;

use app\model\SyncMainJobModel;
use app\job\SyncJob;


/**
 * 当客户的cookie被更新时
 * 1. 更新客户还在生效的同步任务的status_code为可用状态, 即所有 done_time 为0的都会被更新
 */
class UpdateUserCookie {
    
    public function handle ($uid) {

        SyncMainJobModel::where('uid', $uid)
            ->where('done_time', 0)
            ->update([
                'status_code' => SyncJob::SYNC_PROCESSING_DATA
            ]);

        return true;
    }
}
