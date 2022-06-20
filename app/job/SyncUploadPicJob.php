<?php
namespace app\job;

use think\queue\Job;
use think\facade\Db;

use app\extend\tb\TbGetFolder;
use app\extend\tb\TbStreamUpload;
use app\extend\tb\TbUtil;

use app\model\SyncMainJobModel;
use app\model\OldUserModel;

use app\extend\RecordError;

class SyncUploadPicJob {

    // 检查主任务负载的local字段是否全部都有url值, 如果是表示图片已经全部上传完成
    private function isAllPicComplete ($pics) {

        $all_pics = array_column($pics, 'url');
        foreach ($all_pics as $item) {
            
            if ($item == null) return false;
        }

        return true;
    }


    // 更新主任务的payload
    private function buildNewlyPics ($old_pics, $local_id, $pic_url) {

        $pics = json_decode($old_pics, true);
        foreach ($pics as &$item) {

            // 原图没有local_id选项跳过
            if ($item['type'] == 'origin') continue;

            // 根据图片本地id进行匹配,　如果url为空才赋值
            if ($item['local_id'] == $local_id) {

                if (!$item['url']) {
                    
                    $item['url'] = $pic_url;
                }
                break;
            }
        }

        return $pics;
    }

    public function fire(Job $job, $data){

        var_dump($job->getQueue());

        // var_dump('111122223333');
        // var_dump($job);

    }

    public function fire2(Job $job, $data){

        // 数据解构


        
        // 事务
        Db::startTrans();
        // try {

            // 锁行查询, 避免同时更改
            $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
                ->lock(true)
                ->find();
                
            // 任务状态
            $status_code = $syncMainJob->status_code;


            // 1. 以下情况, 把任务删了, 然后退出
            if (
                // 系统错误
                $status_code == SyncMainJobModel::SYSTEM_ERROR ||
                // 上传的文件名非法
                $status_code == SyncMainJobModel::UPLOAD_FILENAME_ERR
            ) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    "[1]系统错误或上传文件名非法, 主任务id: " . $syncMainJob->id,
                    '同步上传图片异常'
                );

                // 设置done_time时间
                $syncMainJob->done_time = time();

                // 需要提交, 不然重发后可能还保持锁着的状态
                Db::commit();
                
                // 删除
                $job->delete();
                return;
            }


            // 2. 以下状态无需执行后续逻辑但要重发任务
            if (
                // 用户淘宝未登录
                $status_code == SyncMainJobModel::TB_UN_LOGIN ||
                // 图片空间满了
                $status_code == SyncMainJobModel::TB_PIC_SPACE_FULL ||
                // 用户暂停同步
                $status_code == SyncMainJobModel::SYNC_PAUSE
            ) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    '[2]任务重发',
                    '同步上传图片异常'
                );

                // 需要回滚, 不然重发后可能还保持锁着的状态
                Db::rollback();

                // 重发
                $job->release(config('sync.release_delay'));
                return;
            }

            // 3. 取出客户cookie
            $cookie = OldUserModel::where('user_id', $syncMainJob->uid)->find()->cookie;

            // 4. 上传图片
            $upload_result = (new TbStreamUpload)->handle([
                'cookie' => $cookie,
                'pic_path' => $local_path,
                'folder_id' => $qianhui_dir_id,
            ]);
            halt($upload_result);
            // 上传异常, 重发任务, 记录状态
            if ($upload_result['error']) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    '[4]任务重发',
                    '同步上传图片异常'
                );

                // 更新状态
                $syncMainJob->status_code = $upload_result['code'];
                $syncMainJob->save();
                Db::commit();

                $job->release(config('sync.release_delay'));
                return;
            }

            
            
            // 5. 更新主任务的负载数据

            // 当前图片的淘宝url
            $pic_url = $upload_raw['data']['url'];
            
            // 生成新一版的payload数据
            $newly_pics = $this->buildNewlyPics($syncMainJob->pics, $local_id, $pic_url);

            // 更新到数据库
            $syncMainJob->pics = json_encode($newly_pics, 320);
            
            // 更新进度
            $syncMainJob->progress = SyncMainJobModel::buildProgress($newly_pics);

            // 如果全部图片已上传完成, 任务状态切换成"正在同步"
            if ($this->isAllPicComplete($newly_pics)) {

                $syncMainJob->status_code = SyncMainJobModel::SYNCING;
            }

            // 提交事务
            $syncMainJob->save();
            Db::commit();


            // 删除该任务
            $job->delete();
        // } catch (\Throwable $th) {

        //     Db::rollback();
            
        //     // 记录错误
        //     RecordError::handle(__CLASS__, $th);

        //     // 重发这个任务, 延迟2秒
        //     $job->release(config('sync.release_delay'));
        // }
    }
    

    public function failed($data){
    
        // ...任务达到最大重试次数后，失败了
    }

}