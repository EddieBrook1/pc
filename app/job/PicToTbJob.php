<?php
namespace app\job;

use think\queue\Job;
use app\extend\tb\TbGetFolder;
use app\extend\tb\TbMediaUpload;
use think\facade\Db;
use app\model\SyncMainJobModel;
use app\extend\RecordError;
use app\extend\Util;

class PicToTbJob{

    // 检查主任务负载的local字段是否全部都有url值, 如果是表示图片已经全部上传完成
    private function isAllPicComplete ($payload) {

        $all_pics = array_column($payload['local'], 'url');
        foreach ($all_pics as $item) {
            
            if ($item == null) return false;
        }

        return true;
    }


    // 计算任务进度
    private function buildProgress ($payload) {
        
        $origin = $payload['origin'];
        $local = $payload['local'];

        // 已上传完成的数量, 包括原图链接数
        $num = count($origin);

        // 累计本地图片已上传完成的数量
        foreach ($local as $item) {

            if ($item['url']) $num += 1;
        }


        // 图片总数
        $total = count($origin) + count($local);

        // 计算百分比
        $progress = floor($num / $total * 100);

        // 乘以权重
        $progress = $progress * config('sync.upload_pic_weight');

        return $progress;
    }


    // 更新主任务的payload
    private function buildNewlyPayload ($old_payload, $pic_id, $url_now) {

        $payload = json_decode($old_payload, true);
        foreach ($payload['local'] as &$item) {

            // 根据图片本地id进行匹配,　如果url为空才赋值
            if ($item['pic_id'] == $pic_id) {

                if (!$item['url']) {
                    
                    $item['url'] = $url_now;
                }
                break;
            }
        }

        return $payload;
    }


    public function fire(Job $job, $data){
        
        // 用户cookie
        $cookie = file_get_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/cookie.txt');

        // 数据解构
        [
            'main_job_id' => $main_job_id,
            'pic_id' => $pic_id,
            'pic_path' => $pic_path,
            'goods_id' => $goods_id,
        ] = $data;


        // 1. 如果任务状态为客户未登录, 无需执行后续逻辑
        $code = json_decode(SyncMainJobModel::find($main_job_id)->result_json, true)['code'];
        if ($code == 1006) return;

        
        // 2. 客户图片空间神笔文件夹的id, 如果异常则记录状态并重发这个任务
        $raw = (new TbGetFolder)->handle($cookie);
        if ($raw['error']) {

            SyncMainJobModel::where('id', $main_job_id)->update([
                'result_json' => json_encode($raw, 320)
            ]);

            $job->release(2);
            return;
        }
        $folder_id = $raw['data'];


        // 3. 上传图片, 如果异常则记录状态, 并重发这个任务
        $raw = (new TbMediaUpload)->handle([
            'cookie' => $cookie,
            'pic_name' => time() . mt_rand(10000, 99999),
            'pic_path' => $pic_path,
            'goods_id' => $goods_id,
            'folder_id' => $folder_id,
        ]);
        if ($raw['error']) {

            SyncMainJobModel::where('id', $main_job_id)->update([
                'result_json' => json_encode($raw, 320)
            ]);

            $job->release(2);
            return;
        }
        $url_now = $raw['data']['url'];


        // 4. 更新主任务的负载数据
        Db::startTrans();
        try {
            
            // 锁行查询, 避免同时更改
            $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
                ->lock(true)
                ->find();
            
            // 获取新的payload数据
            $newly_payload = $this->buildNewlyPayload($syncMainJob->payload, $pic_id, $url_now);

            // 更新到数据库
            $syncMainJob->payload = json_encode($newly_payload, 320);
            
            // 更新进度
            $syncMainJob->progress = $this->buildProgress($newly_payload);

            // 如果全部图片已上传完成, 任务状态切换成"正在同步"
            if ($this->isAllPicComplete($newly_payload)) {

                $syncMainJob->result_json = json_encode(Util::formatResult([], true, 'SYNCING'), 320);
            }

            // 提交事务
            $syncMainJob->save();
            Db::commit();
            // 删除该任务
            $job->delete();
        } catch (\Throwable $th) {

            Db::rollback();
            
            // 记录错误
            RecordError::handle(__CLASS__, $th);

            // 重发这个任务, 延迟2秒
            $job->release(2);
        }
    }
    

    public function failed($data){
    
        // ...任务达到最大重试次数后，失败了
    }

}