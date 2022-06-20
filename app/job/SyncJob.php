<?php
namespace app\job;

use think\queue\Job;
use think\facade\Db;

use app\extend\tb\TbStreamUpload;
use app\extend\tb\TbCommitItemTemplate;
use app\extend\tb\TbUtil;

use app\model\SyncMainJobModel;
use app\model\OldUserModel;

use app\extend\RecordError;


class SyncJob {

    // 队列名
    private const QUEUE_NAME_UPLOAD = 'sync_upload_pic';
    private const QUEUE_NAME_COMMIT = 'sync_commit';

    // 任务状态码, 同 config/code.php
    public const SYNCING = 1008;                    // 正在同步
    public const SYNC_COMPLETE = 1009;              // 同步成功
    public const SYNC_PROCESSING_DATA = 1010;       // 正在处理模版数据...
    public const TB_PIC_SPACE_FULL = 1005;          // 图片空间满了
    public const TB_UN_LOGIN = 1006;                // 淘宝未登录
    public const SYSTEM_ERROR = 1007;               // 系统错误
    public const SYNC_PAUSE = 1017;                 // 同步暂停
    public const UPLOAD_FILENAME_ERR = 1027;        // 上传失败, 文件名含有非法字符
    public const TB_WIRELESS_LINK_ERR = 1032;        // 上传失败, 热区链接非淘系无线端链接
    public const SYNC_PREPARE = 1042;               // 同步预处理、数据包含动图、需用文本编辑方式同步
    public const SYNC_ERR = 1040;                   // 同步失败

    // 允许的状态码
    public const ACCEPT_CODE = [
        self::SYNCING,
        self::SYNC_COMPLETE,
        self::SYNC_PROCESSING_DATA,
        self::TB_PIC_SPACE_FULL,
        self::TB_UN_LOGIN,
        self::SYSTEM_ERROR,
        self::SYNC_PAUSE,
        self::UPLOAD_FILENAME_ERR,
        self::TB_WIRELESS_LINK_ERR
    ];


    // 任务权重, 用来计算百分比
    public const UPLOAD_PIC_WEIGHT = '0.7';  // 上传图片
    public const COMMIT_WEIGHT = '0.3';      // 同步任务


    /** 判断错误代码是否可用, 不可用返回系统错误码然后记录日志
     * 防止工具栏里的返回值更新而导致状态码混乱无法管理
     *
     * @param [Number] $code
     * @return Number
     */
    public static function getCodeOrRecordLog ($code) {

        if (in_array($code, self::ACCEPT_CODE)) {
            return $code;
        } else {
            RecordError::handle(
                __CLASS__,
                "原状态码: $code",
                '状态码变更'
            );
            return self::SYSTEM_ERROR;
        }
    }


    /** 计算任务进度
     *
     * @param [Array] $resource_object
     * @return Number
     */
    public static function buildProgress ($resource_object) {

        $resource_object = json_decode($resource_object, true);

        // 已上传图片数
        $uploaded = 0;
        foreach ($resource_object as $item) {
            if ($item['url'] !== '') $uploaded += 1;
        }

        // 图片总数
        $total = count($resource_object);

        // 计算百分比
        $progress = floor($uploaded / $total * 100 * self::UPLOAD_PIC_WEIGHT);

        return $progress;
    }


    /** 判断是否全部图片都上传完成, 根据是否都有url判断
     *
     * @param [Array] $resource_object
     * @return boolean
     */
    public static function isAllPicComplete ($resource_object) {

        $resource_object = json_decode($resource_object, true);
        $all_url = array_column($resource_object, 'url');
        foreach ($all_url as $item) {
            if ($item == '') return false;
        }

        return true;
    }


    // 入口
    public function fire (Job $job, $data) {

        $queue_name = $job->getQueue();
        
        // 上传图片
        if ($queue_name === self::QUEUE_NAME_UPLOAD) {
            return (new SyncUploadPic)->handle($job, $data);
        }

        // 提交同步
        if ($queue_name === self::QUEUE_NAME_COMMIT) {
            return (new SyncCommit)->handle($job, $data);
        }

        // 未匹配到任何处理方法, 删除任务
        RecordError::handle(
            __CLASS__,
            "任务名: $queue_name",
            'SyncJob 未匹配到处理方法'
        );
        $job->delete();
    }
}


/** 处理上传图片的任务
 * 1. 判断任务是否要终止
 * 2. 判断任务是否可执行
 * 3. 上传图片
 * 4. 更新主任务负载数据
 * 
 * 更改主任务状态的场景
 *      - 上传图片失败
 *      - 全部图片上传完成
 * 
 */
class SyncUploadPic {

    // 重发任务延迟2秒
    private const RELEASE_DELAY = 2;


    // 更新主任务的payload
    private function buildNewlyResourceObject ($old_recource_object, $local_id, $pic_url) {

        $old_recource_object = json_decode($old_recource_object, true);
        foreach ($old_recource_object as &$item) {

            // 原图不需要处理
            if (!$item['is_local']) continue;

            // 根据图片本地id进行匹配,　如果url为空才赋值
            if ($item['local_id'] == $local_id) {
                if ($item['url'] == '') {
                    $item['url'] = $pic_url;
                }
                break;
            }
        }

        return json_encode($old_recource_object);
    }


    // 入口
    public function handle ($job, $data) {

        $main_job_id    = $data['main_job_id'];     // 主任务id
        $local_id       = $data['local_id'];        // 本地图片数据库id
        $local_path     = $data['local_path'];      // 本地图片地址
        $goods_id       = $data['goods_id'];        // 淘宝商品id
        $qianhui_dir_id = $data['qianhui_dir_id'];  // 名叫“千绘”的文件夹id

        Db::startTrans();
        try {
            
            // 主任务
            $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
                ->lock(true)
                ->find();

            // 任务状态
            $status_code = $syncMainJob->status_code;

            
            /* ---------- 1. 以下情况, 把任务删了, 无需执行后续逻辑 ---------- */
            if (
                // 图片空间满了
                $status_code == SyncJob::TB_PIC_SPACE_FULL ||
                // 用户淘宝未登录
                $status_code == SyncJob::TB_UN_LOGIN ||
                // 系统错误
                $status_code == SyncJob::SYSTEM_ERROR ||
                // 上传的文件名非法
                $status_code == SyncJob::UPLOAD_FILENAME_ERR
            ) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    "[1]系统错误或上传文件名非法, 主任务id: " . $syncMainJob->id,
                    '同步上传图片异常'
                );

                // 终止时间
                $syncMainJob->done_time = time();
                $syncMainJob->save();

                // 提交, 不然重发后可能还保持锁着的状态
                Db::commit();

                $job->delete();
                return;
            }


            /* ---------- 2. 以下状态无需执行后续逻辑但要重发任务 ---------- */
            if (
                // 用户暂停同步
                $status_code == SyncJob::SYNC_PAUSE
            ) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    '[2]任务重发, 未登录、图片空间满了或用户暂停同步',
                    '同步上传图片异常'
                );

                // 需要回滚, 不然重发后可能还保持锁着的状态
                Db::rollback();

                // 重发
                $job->release(config('sync.release_delay'));
                return;
            }


            /* -------------------- 3. 上传图片 -------------------- */
            // 客户cookie
            $cookie = OldUserModel::where('user_id', $syncMainJob->uid)->find()->cookie;

            // 上传图片
            $upload_result = (new TbStreamUpload)->handle([
                'cookie' => $cookie,
                'pic_path' => $local_path,
                'folder_id' => $qianhui_dir_id,
            ]);


            // 上传异常
            if ($upload_result['error']) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    '[3]任务重发, ' . $upload_result['msg'],
                    '同步上传图片异常'
                );

                // 更新状态
                $syncMainJob->status_code = SyncJob::getCodeOrRecordLog($upload_result['code']);
                $syncMainJob->save();
                Db::commit();

                $job->release(self::RELEASE_DELAY);
                return;
            }


            /* ---------- 4. 更新主任务负载数据 ---------- */
            // 上传成功的图片链接
            $pic_url = $upload_result['data']['url'];

            // 生成新一版的payload数据
            $newly_resource_object = $this->buildNewlyResourceObject($syncMainJob->resource_object, $local_id, $pic_url);

            // 计算新的进度
            $newly_progress = SyncJob::buildProgress($newly_resource_object);

            // 检查是否全部图片都上传完成, 如果上传完成切换状态成 “正在同步”
            $newly_statuc_code = SyncJob::isAllPicComplete($newly_resource_object)
                ? SyncJob::SYNCING
                : $syncMainJob->status_code;

            // 保存
            $syncMainJob->resource_object = $newly_resource_object;
            $syncMainJob->progress = $newly_progress;
            $syncMainJob->status_code = $newly_statuc_code;
            $syncMainJob->save();
            Db::commit();
            
            // 删除该任务
            $job->delete();

        } catch (\Throwable $th) {

            Db::rollback();

            // 记录日志
            RecordError::handle(
                __CLASS__,
                $th,
                '同步上传图片异常, 逻辑异常'
            );

            // 重发任务
            $job->release(self::RELEASE_DELAY);
        }
    }
}


/**
 * 处理同步的任务
 * 1. 判断任务是否要终止
 * 2. 判断任务是否可执行
 * 3. 提交同步
 * 4. 更新任务状态
 * 
 * 更改主任务状态的场景
 *      - 提交同步异常
 *      - 同步完成
 * 
 */
class SyncCommit {

    // 重发任务延迟时间, 单位: 秒
    private const RELEASE_DELAY = 10;


    // 入口
    public function handle ($job, $data) {

        // 主任务id
        $main_job_id = $data['main_job_id'];

        Db::startTrans();
        try {
            
            // 主任务
            $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
                ->lock(true)
                ->find();

            // 任务状态
            $status_code = $syncMainJob->status_code;
            $resource_object = $syncMainJob->resource_object;

            /* --------------- 1. 以下情况, 把任务删了, 无需执行后续逻辑 --------------- */
            if (
                // 图片空间满了
                $status_code == SyncJob::TB_PIC_SPACE_FULL ||
                // 用户淘宝未登录
                $status_code == SyncJob::TB_UN_LOGIN ||
                // 系统错误
                $status_code == SyncJob::SYSTEM_ERROR ||
                // 上传的文件名非法
                $status_code == SyncJob::UPLOAD_FILENAME_ERR
            ) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    "[1]系统错误或上传文件名非法, 主任务id: " . $syncMainJob->id,
                    '同步提交数据异常'
                );

                // 终止时间
                $syncMainJob->done_time = time();
                $syncMainJob->save();

                // 提交, 不然重发后可能还保持锁着的状态
                Db::commit();

                $job->delete();
                return;
            }


            /* ---------- 2. 以下状态无需执行后续逻辑但要重发任务 ---------- */
            if (
                // 图片未上传完成
                !SyncJob::isAllPicComplete($resource_object) ||
                // 用户暂停同步
                $status_code == SyncJob::SYNC_PAUSE
            ) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    '[2]任务重发, 用户淘宝未登录、图片未上传完成或暂停同步',
                    '同步提交数据异常'
                );

                // 需要回滚, 不然重发后可能还保持锁着的状态
                Db::rollback();

                // 重发
                $job->release(self::RELEASE_DELAY);
                return;
            }


            /* -------------------- 3. 进行同步操作 -------------------- */
            // 用户cookie
            $cookie = OldUserModel::where('user_id', $syncMainJob->uid)->find()->cookie;

            // 同步
            $commit_raw = (new TbCommitItemTemplate)->handle($cookie, $main_job_id);

            // 同步异常
            if ($commit_raw['error']) {

                // 记录日志
                RecordError::handle(
                    __CLASS__,
                    '[3]任务重发, ' . $commit_raw['msg'],
                    '同步提交数据异常'
                );

                // 更新状态
                $syncMainJob->status_code = SyncJob::getCodeOrRecordLog($commit_raw['code']);
                $syncMainJob->save();
                Db::commit();

                // 重发
                $job->release(self::RELEASE_DELAY);
                return;
            }


            /* --------------- 4. 更新主任务状态 --------------- */
            if ($commit_raw['code'] === SyncJob::SYNC_PREPARE) {

                // 新的状态码
                $newly_statuc_code = SyncJob::SYNC_PREPARE;

                // 文本编辑格式的数据
                $syncMainJob->template_content_text = $commit_raw['data']['template_content_text'];

                // 完成时间
                $syncMainJob->done_time = time();
            } else {

                // 新的状态码
                $newly_statuc_code = SyncJob::SYNC_COMPLETE;

                // 进度
                $newly_progress = 100;

                // 完成时间
                $done_time = time();

                $syncMainJob->progress = $newly_progress;
                $syncMainJob->done_time = $done_time;
            }

            // 保存
            $syncMainJob->status_code = $newly_statuc_code;
            $syncMainJob->save();
            Db::commit();

            // 删除任务
            $job->delete();

        } catch (\Throwable $th) {

            Db::rollback();

            // 记录日志
            RecordError::handle(
                __CLASS__,
                $th,
                '同步上传图片异常, 逻辑异常'
            );

            // 重发任务
            $job->release(self::RELEASE_DELAY);
        }
    }
}