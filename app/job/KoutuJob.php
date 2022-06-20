<?php
namespace app\job;

use think\queue\Job;
use app\extend\DoCurl;

class KoutuJob{

    public function fire(Job $job, $data){
            
        // if ($job->attempts() > 3) {
            //通过这个方法可以检查这个任务已经重试了几次了
        // }
        
        
        //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
        // $job->delete();
        
        // 也可以重新发布这个任务
        // $job->release($delay); //$delay为延迟时间

        $time = time();
        $doCurl = new DoCurl;
        $res = $doCurl->get('http://test.qianzhiyu.net/test/test9');
        file_put_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/b.txt', $time . '---' . $res . "\r\n\r\n", FILE_APPEND);
    }
    

    public function failed($data){
    
        // ...任务达到最大重试次数后，失败了
    }

}