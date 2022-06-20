<?php
namespace app\job;

use think\queue\Job;
use app\model\SyncMainJobModel;
use app\extend\tb\TbCommitItemTemplate;
use app\model\OldUserModel;
use think\facade\Db;
use app\extend\RecordError;

class SyncCommitJob {
    
    public function fire (Job $job, $data) {

        $main_job_id = $data['main_job_id'];
        

        // 事务
        Db::startTrans();
        try {
            
            // 锁行查询, 避免同时更改
            $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
                ->lock(true)
                ->find();

            // 任务状态
            $status_code = $syncMainJob->status_code;
            
            // 1. 以下情况无需执行后续逻辑
            if (
                // 用户淘宝未登录无需执行后续逻辑
                $status_code == SyncMainJobModel::TB_UN_LOGIN ||
                // 图片未上传完成
                !$syncMainJob->is_all_uploaded ||
                // 用户暂停同步
                $status_code == SyncMainJobModel::SYNC_PAUSE
            ) {

                // 需要回滚, 不然重发后可能还保持锁着的状态
                Db::rollback();

                // 重发
                $job->release(config('sync.release_delay'));
                return;
            }

            // 如果是系统异常, 把任务删了, 然后退出
            if ($status_code == SyncMainJobModel::SYSTEM_ERROR) {

                // 设置done_time时间
                $syncMainJob->done_time = time();

                // 需要提交, 不然重发后可能还保持锁着的状态
                Db::commit();

                // 删除
                $job->delete();
                return;
            }


            // 2. 取出用户cookie
            $cookie = OldUserModel::where('user_id', $syncMainJob->uid)->find()->cookie;


            // 3. 进行同步操作, 如果出错记录状态重发任务
            $commit_raw = (new TbCommitItemTemplate)->handle([
                'cookie' => $cookie,
                'main_job_id' => $main_job_id,
            ]);
            if ($commit_raw['error']) {

                // 更新状态
                $syncMainJob->status_code = $commit_raw['code'];
                $syncMainJob->save();
                Db::commit();

                // 重发
                $job->release(config('sync.release_delay'));
                return;
            }


            // 4. 更新成功

            // 更新主任务状态为 "同步成功"
            $syncMainJob->status_code = SyncMainJobModel::SYNC_COMPLETE;
            
            // 进度改成 100
            $syncMainJob->progress = 100;

            // 设置done_time时间
            $syncMainJob->done_time = time();

            $syncMainJob->save();
            Db::commit();

            // 删除任务
            $job->delete();
        } catch (\Throwable $th) {

            Db::rollback();

            // 记录错误
            RecordError::handle(__CLASS__, $th);

            // 重发这个任务, 延迟2秒
            $job->release(config('sync.release_delay'));
        }
    }
}