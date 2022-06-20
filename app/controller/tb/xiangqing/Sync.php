<?php
namespace app\controller\tb\xiangqing;

use app\BaseController;
use think\Request;
use think\facade\Validate;
use think\facade\Queue;
use think\facade\Db;

use app\model\SyncMainJobModel;
use app\model\UploadModel;
use app\model\OldUserModel;
use app\model\OldUserRoleModel;
use app\model\OldDetailTemplateModel;

use app\extend\Util;
use app\extend\EditorDataValidator;
use app\extend\CssValidator;
use app\extend\tb\TbUtil;
use app\extend\RecordError;

use app\job\SyncJob;
use app\controller\tb\xiangqing\EditorDataToDetail;


class Sync extends BaseController {

    // 状态码, 同config/code.php
    public const SYNC_COMPLETE = 1009; // 同步成功
    public const SYNC_ERR = 1040;      // 同步失败
    
    // 历史同步记录时效, 24小时
    public const HISTORY_EXPIRE = 60 * 60 * 24;

    /**
     * 生成所有子任务的负载数据
     *
     * @param [Number] $main_job_id  主任务id
     * @param [Array] $input pc方法的入参
     * @return Array 形如:
        [[
            'main_job_id' => 1,
            'pic_id' => 26,
            'pic_path' => 'xxx/xxx/xxx/aaa.jpg',
            'goods_id' => xxxxx,
        ], ...]
     * 
     */
    private function buildUploadJobPayloads ($param) {

        [
            'main_job_id'     => $main_job_id,
            'goods_id'        => $goods_id,
            'resource_object' => $resource_object,
            'qianhui_dir_id'          => $qianhui_dir_id
        ] = $param;

        $result = [];
        foreach ($resource_object as $item) {
            
            if (!$item['is_local']) continue;
            array_push($result, [
                // 主任务id
                'main_job_id'   => $main_job_id,
                // 图片在数据库的id
                'local_id'        => $item['local_id'],
                // 图片本地地址
                'local_path'      => null,
                // 商品id
                'goods_id'      => $goods_id,
                // 千绘文件夹id
                'qianhui_dir_id' => $qianhui_dir_id,
            ]);
        }

        // 填充本地地址, 后面消费job时, 就无需重新打开数据库查询
        $local_paths = UploadModel::whereIn('id', array_column($result, 'local_id'))->column('local_path', 'id');
        foreach ($result as &$item) {

            $item['local_path'] = $local_paths[$item['local_id']];
        }

        return $result;
    }


    // 执行同步操作
    private function exec ($uid, $input, $client_type) {

        // 验证入参
        $isOk = SyncPcValidator::handle($input);
        if ($isOk !== true) {

            RecordError::handle(
                __CLASS__,
                '参数: ' . \json_encode($input, 320),
                '同步入参异常'
            );

            return $this->resultJson([], false, 'PARAM_ERR', $isOk);
        }

        // 商品id
        $goods_id = $input['goods_id'];
        // 模版id
        $template_id = $input['template_id'];
        // 编辑器数据
        $editor_data = json_decode($input['editor_data'], true);
        // 图片数据
        $resource_object = json_decode($input['resource_object'], true);
        // 客户cookie
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 模版是否存在
        if (!XiangqingUtil::isTemplateUsable($template_id)) return $this->resultJson([], false, 'TEMPLATE_NULL_ERR');

        // 查看是否过期
        if (!XiangqingUtil::isPayExpire($uid, $template_id)) return $this->resultJson([], false, 'SERVICE_NO');

        // 新数据格式转成表数据
        $detail = EditorDataToDetail::handle($editor_data);
        Db::startTrans();
        try {
            
            // 加锁查询有没有这条数据
            $detailTemplate = OldDetailTemplateModel::where('tid', $template_id)
                ->where('gid', $goods_id)
                ->where('uid', $uid)
                ->order('id', 'DESC')
                ->lock(true)
                ->find();

            
            if ($detailTemplate) {

                // 有更新数据
                $detailTemplate->template = $detail['template'];
                $detailTemplate->hotspot = $detail['hotspot'];
                $detailTemplate->gif = $detail['gif'];
                $detailTemplate->video = $detail['video'];
                $detailTemplate->update_time = time();
                $detailTemplate->save();
            } else {

                // 没有创建一条
                $detailTemplate = OldDetailTemplateModel::create([
                    'template' => $detail['template'],
                    'uid' => $uid,
                    'tid' => $template_id,
                    'gid' => $goods_id,
                    'hotspot' => $detail['hotspot'],
                    'gif' => $detail['gif'],
                    'video' => $detail['video'],
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
            Db::commit();

        } catch (\Throwable $th) {

            Db::rollback();
            $detailTemplate = null;

            RecordError::handle(
                __CLASS__,
                $th->getMessage() . "; 入参(uid=$uid, template_id=$template_id, goods_id=$goods_id)",
                "同步时, 详情模版数据保存失败(uid+tid+gid)"
            );
        }

        // 保存失败, 退出
        if (!$detailTemplate) return $this->resultJson([], false, 'SYNC_SUBMIT_FAIL', '保存失败');

        // 判断用户是否有额外权限
        $isOk = TemplateAuthValidator::handle($editor_data, $uid, $template_id);
        if ($isOk !== true) return $this->resultJson([], false, $isOk);

        // 判断客户cookie还能不能用
        if (!TbUtil::isCookieValid($cookie)) return $this->resultJson([], false, 'TB_UN_LOGIN');


        // 获取千绘文件夹id
        $qianhui_dir_id = TbUtil::getQianhuiDirIdOrBuild($cookie);
        if (!$qianhui_dir_id) {
            RecordError::handle(
                __CLASS__,
                '无法获取“千绘”文件夹id',
                '创建同步时异常'
            );
            return $this->resultJson([], false, 'TB_UN_LOGIN');
        }


        // 判断用户是否有正在进行的同步任务, 如果已存在则不允许重复创建
        $syncMainJob = SyncMainJobModel::where('goods_id', $goods_id)
            ->where('done_time', 0)
            ->find();
        if ($syncMainJob) return $this->resultJson([], false, 'SYNC_JOB_EXIST');


        // 创建主任务
        $syncMainJob = SyncMainJobModel::create([
            // 用户id
            'uid'           => $uid,
            // 模版id
            'template_id'   => $template_id,
            // 商品id
            'goods_id'      => $goods_id,
            // 编辑器数据
            'editor_data'   => $input['editor_data'],
            // 同步哪一端, 0电脑端、1手机端、2电脑端和手机端
            'client_type'   => $client_type,
            // 要上传的图片, 包括原图等信息
            'resource_object' => $input['resource_object'],
            // 默认状态为正在处理模版数据...
            'status_code'   => SyncJob::SYNC_PROCESSING_DATA,
            // 任务进度
            'progress'      => SyncJob::buildProgress($input['resource_object']),
            // 任务创建时间
            'create_time'   => time(),
            // 任务结束时间
            'done_time'     => 0
        ]);


        // ------ 创建子任务-上传图片 -------
        // 生成负载数据
        $upload_payload_arr = $this->buildUploadJobPayloads([
            'main_job_id' => $syncMainJob->id,
            'goods_id' => $goods_id,
            'resource_object' => $resource_object,
            'qianhui_dir_id' => $qianhui_dir_id
        ]);
        $upload_job_name = 'sync_upload_pic';                                           // 任务名
        $upload_job_handle = config("jobname.$upload_job_name");                        // 任务处理类
        foreach ($upload_payload_arr as $item) {

            Queue::push($upload_job_handle, $item, $upload_job_name);
        }

        
        // 创建子任务-同步
        $commit_payload = [ 'main_job_id' => $syncMainJob->id ];                // 负载数据
        $commit_job_name = 'sync_commit';                                       // 任务名
        $commit_job_handle = config("jobname.$commit_job_name");                // 任务处理类
        Queue::push($commit_job_handle, $commit_payload, $commit_job_name);


        // 返回成功提示
        return $this->resultJson([
            'main_job_id' => $syncMainJob->id
        ], true, 'SYNC_SUBMIT_SUCC');
    }


    // 同步电脑端
    public function pc (Request $request) {

        $uid = $request->uid;
        $input = $request->post();
        $client_type = 0;
        return $this->exec($uid, $input, $client_type);

    }
    

    // 同步手机端
    public function phone (Request $request) {

        $uid = $request->uid;
        $input = $request->post();
        $client_type = 1;
        return $this->exec($uid, $input, $client_type);

    }
    
    
    // 同步两端
    public function both (Request $request) {

        $uid = $request->uid;
        $input = $request->post();
        $client_type = 2;
        return $this->exec($uid, $input, $client_type);

    }


    // 同步暂停
    public function pause (Request $request) {

        $input = $request->post();
        if (!SyncValidator::pause($input)) return $this->resultJson([], false, 'PARAM_ERR');

        $main_job_id = $input['main_job_id'];
        $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
            ->where('done_time', 0)
            ->find();

        // job不存在
        if ($syncMainJob == null) return $this->resultJson([], false, 'PARAM_ERR');

        // 更改状态为同步暂停
        $syncMainJob->status_code = SyncMainJobModel::SYNC_PAUSE;
        $syncMainJob->save();

        // 操作成功
        return $this->resultJson([], true, 'EXEC_SUCC');
    }
    

    // 同步继续
    public function start (Request $request) {

        $input = $request->post();
        if (!SyncValidator::start($input)) return $this->resultJson([], false, 'PARAM_ERR');

        $main_job_id = $input['main_job_id'];
        $syncMainJob = SyncMainJobModel::where('id', $main_job_id)
            ->where('done_time', 0)
            ->find();

        // job不存在
        if ($syncMainJob == null) return $this->resultJson([], false, 'PARAM_ERR');

        // 更改状态为正在同步
        $syncMainJob->status_code = SyncMainJobModel::SYNCING;
        $syncMainJob->save();

        // 操作成功
        return $this->resultJson([], true, 'EXEC_SUCC');
    }

    
    // 获取主任务的状态
    public function mainJobStatus (Request $request) {

        $input = $request->get();
        if (!SyncValidator::mainJobStatus($input)) return $this->resultJson([], false, 'PARAM_ERR');

        $uid = $request->uid;
        $main_job_id = $input['main_job_id'];
        $syncMainJob = SyncMainJobModel::where('uid', $uid)
            ->where('id', $main_job_id)
            ->find();

        // 任务不存在
        if ($syncMainJob == null) return $this->resultJson([], false, 'PARAM_ERR');

        $status_code = $syncMainJob->status_code;
        $msg = Util::getCodeMsgByCode($status_code);
        $progress = $syncMainJob->progress;

        return $this->resultJson([
            'status_code' => $status_code,
            'msg' => $msg,
            'progress' => $progress,
        ], true, 'GET_SUCC');
    }


    // 获取一段时间内的同步记录
    public function history (Request $request) {

        $input = $request->get();
        if (!SyncValidator::history($input)) return $this->resultJson([], false, 'PARAM_ERR');
        
        $uid = $request->uid;
        $page = isset($input['page']) ? $input['page'] : null;
        $size = isset($input['size']) ? $input['size'] : null;
        $time = time() - self::HISTORY_EXPIRE;

        if ($page && $size) {

            $data = SyncMainJobModel::where('uid', $uid)
                ->field('id, progress, status_code, goods_id, template_id, client_type, create_time, done_time')
                ->where('create_time', '>', $time)
                ->order('create_time', 'desc')
                ->page($page, $size)
                ->select()
                ->toArray();

        } else {

            $data = SyncMainJobModel::where('uid', $uid)
                ->field('id, progress, status_code, goods_id, template_id, client_type, create_time, done_time')
                ->where('create_time', '>', $time)
                ->order('create_time', 'desc')
                ->select()
                ->toArray();

        }


        return $this->resultJson($data, true, 'GET_SUCC');
    }


    // 获取文本格式的模版数据
    public function getPrepareData (Request $request) {

        $input = $request->get();
        if (!SyncValidator::getPrepareData($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 主任务id
        $main_job_id = $input['main_job_id'];
        $syncMainJob = SyncMainJobModel::field('id, progress, status_code, goods_id, template_id, client_type, template_content_text, done_time')
            ->where('id', $main_job_id)
            ->find();

        // 模版不存在
        if ($syncMainJob == null) return $this->resultJson([], false, 'UNDEFINED');

        
        // 如果任务已经完成或失败, 则把文本数据返回空值
        $result = $syncMainJob->toArray();
        // if ($result['done_time'] != 0) {
        //     $result['template_content_text'] = null;
        // }
        unset($result['done_time']);

        return $this->resultJson($result, true, 'GET_SUCC');
    }


    // 更改主任务状态
    public function setMainJobStatus (Request $request) {

        $input = $request->post();
        if (!SyncValidator::setMainJobStatus($input)) return $this->resultJson([], false, 'PARAM_ERR');

        $uid = $request->uid;
        $main_job_id = $input['main_job_id'];
        $status_code = $input['status_code'];

        Db::startTrans();
        try {
            
            $syncMainJob = SyncMainJobModel::where('uid', $uid)
                ->where('id', $main_job_id)
                ->lock(true)
                ->find();

            // 主任务不存在
            if ($syncMainJob == null) {
                Db::rollback();
                return $this->resultJson([], false, 'UNDEFINED');
            }

            // 主任务已经是同步成功或同步失败的状态, 禁止操作
            if ($syncMainJob->status_code == self::SYNC_COMPLETE || $syncMainJob->status_code == self::SYNC_ERR) {
                Db::rollback();
                return $this->resultJson([], false, 'EXEC_FAIL');
            }

            // 更新状态
            $syncMainJob->status_code = $status_code;
            $syncMainJob->done_time = time();
            $syncMainJob->save();
            Db::commit();

        } catch (\Throwable $th) {

            Db::rollback();
            RecordError::handle(
                __CLASS__,
                $th->getMessage(),
                '更新主任务状态流程错误'
            );

            return $this->resultJson([], false, 'SYSTEM_ERROR');
        }

        return $this->resultJson([], true, 'EXEC_SUCC');
    }
}


class SyncValidator {

    public static function pause ($input) {

        $validate = Validate::rule([
            'main_job_id' => 'require|number',
        ]);

        return $validate->check($input);
    }


    public static function start ($input) {

        $validate = Validate::rule([
            'main_job_id' => 'require|number',
        ]);

        return $validate->check($input);
    }


    public static function mainJobStatus ($input) {

        $validate = Validate::rule([
            'main_job_id' => 'require|number',
        ]);

        return $validate->check($input);
    }


    public static function getPrepareData ($input) {

        $validate = Validate::rule([
            'main_job_id' => 'require|number',
        ]);
        return $validate->check($input);

    }


    public static function setMainJobStatus ($input) {

        $accept_code = [
            Sync::SYNC_COMPLETE,   // 同步成功
            Sync::SYNC_ERR,        // 同步失败
        ];

        $validate = Validate::rule([
            'main_job_id' => 'require|number',
            'status_code' => 'require|number|in:' . implode(',', $accept_code)
        ]);
        return $validate->check($input);
    }


    public static function history ($input) {
        $validate = Validate::rule([
            'page' => 'number|min:1',
            'size' => 'number|min:1',
        ]);
        return $validate->check($input);
    }
}


class SyncPcValidator {

    // 允许的资源类型
    private const RESOURCE_TYPE_ACCEPT = [
        'normal',
        'video',
        'gif',
    ];

    // 视频允许的长度
    private const VIDEO_MAX_LENGTH = 120;

    // resource_object格式是否可用
    private static function isResourceObjectOk ($resource_object_raw) {

        // 尝试转换成数组
        try {
            
            $resource_object = \json_decode($resource_object_raw, true);
            
        } catch (\Throwable $th) { $resource_object = null; }

        // 不是数组退出
        if ($resource_object == null) return false;

        // 检查内部结构
        foreach ($resource_object as $index => $item) {
            if (
                !array_key_exists('width', $item) ||
                !array_key_exists('height', $item) ||
                !array_key_exists('is_local', $item) ||
                !array_key_exists('local_id', $item) ||
                !array_key_exists('type', $item) ||
                !array_key_exists('url', $item) ||
                !array_key_exists('mk_index', $item)
            ) return 'resource_object 缺少字段';

            $width = $item['width'];
            $height = $item['height'];
            $is_local = $item['is_local'];
            $local_id = $item['local_id'];
            $type = $item['type'];
            $url = $item['url'];
            $mk_index = $item['mk_index'];

            if (!CssValidator::isPxSize($width)) return "resource_object[$index]['width']不可用";
            if (!CssValidator::isPxSize($height)) return "resource_object[$index]['height']不可用";
            if (!\is_bool($is_local)) return "resource_object[$index]['is_local']不可用";
            if (!\in_array($type, self::RESOURCE_TYPE_ACCEPT)) return "resource_object[$index]['type']不可用";
            
            if ($is_local) {
                
                if (!\is_numeric($local_id)) return "resource_object[$index]['local_id']不可用";
                if (!\is_numeric($mk_index)) return "resource_object[$index]['mk_index']不可用";
                
            } else {
                
                if (!\is_string($url)) return false;

            }

            return true;
        }
    }


    // 是否含有动图模块
    private static function isHasGif ($editor_data) {

        foreach ($editor_data as $item) {
            
            $mk_type = $item['mk_type'];
            $groups = $item['groups'];

            // 动图模块
            if ($item['mk_type'] === 'gif') return true;

            // 组件里含有动图
            foreach ($groups as $group) {
                if (!empty($group['gifStyle'])) return true;
            }
        }

        return false;
    }


    // 是否含有视频模块
    private static function isHasVideo ($editor_data) {

        foreach ($editor_data as $item) {
            
            $mk_type = $item['mk_type'];
            $groups = $item['groups'];

            // 动图模块
            if ($item['mk_type'] === 'video') return true;

            // 组件里含有动图
            foreach ($groups as $group) {
                if (!empty($group['videoStyle'])) return true;
            }
        }

        return false;
    }


    // 视频不能超过2分钟
    private static function isVideoLengthOk ($editor_data) {

        foreach ($editor_data as $item) {
            $mk_type = $item['mk_type'];
            if ($mk_type !== 'video') continue;

            $video_length = $item['groups'][0]['videoStyle']['length'];
            if ($video_length > self::VIDEO_MAX_LENGTH) return false;
        }

        return true;
    }


    // 入口
    public static function handle ($input) {
        $validate = Validate::rule([
            // 商品id
            'goods_id'      => 'require',
            // 模版id
            'template_id'   => 'require',
            // 模版数据
            'editor_data'   => 'require',
            // 待上传的图片
            'resource_object'  => 'require',
        ]);
        if (!$validate->check($input)) return '缺少必要字段';

        $editor_data = $input['editor_data'];
        $resource_object = $input['resource_object'];

        // 检查editor_data是否可用
        $is_editor_data_ok = EditorDataValidator::handle($editor_data);
        if ($is_editor_data_ok !== true) return $is_editor_data_ok;
        
        // 不允许同时含有动图和视频
        $editor_data = \json_decode($editor_data, true);
        $is_has_gif = self::isHasGif($editor_data);
        $is_has_video = self::isHasVideo($editor_data);
        if ($is_has_gif && $is_has_video) return '不允许同时含有动图和视频';

        // 如果有视频,　视频长度是否可用
        if ($is_has_video) {
            if (self::isVideoLengthOk($editor_data) !== true) return '视频长度不能超过' . self::VIDEO_MAX_LENGTH . '秒';
        }

        // 检查resource_object格式是否可用
        $is_resource_object_ok = self::isResourceObjectOk($resource_object);
        if ($is_resource_object_ok !== true) $is_resource_object_ok;

        return true;
    }
}


/** 模版权限验证器
 * 验证项
 * 是否有动图权限
 * 是否有视频权限
 * 是否有超清权限
 * 是否有超高权限
 * 是否有添加视频链接的权限
 */
class TemplateAuthValidator {

    // 该类可能返回的错误代码
    private const ACCEPT_CODE = [
        'AUTH_XHEIGHT_ERR',
        'AUTH_HDV_ERR',
        'AUTH_HGIF_ERR',
        'AUTH_VIDEO_ERR',
        'AUTH_HVIDEO_ERROR',
    ];

    // 额定高度
    private const X_HEIGHT = 40000;

    // 模块类型
    private const MK_TYPE_GIF = 'gif';
    private const MK_TYPE_VIDEO = 'video';

    // 视频默认设置
    private const VIDEO_WIDTH = 620;    // 视频组件宽度
    private const VIDEO_HEIGHT = 420;   // 限高视频高度


    // 动图是否超高, [2022-1-3]暂时这样写
    private static function isGifXheight ($gif_component) {
        return false;
    }
    

    // 视频是否超高
    private static function isVideoXHeight ($video_component) {

        $videoStyle = $video_component['videoStyle'];
        $width = intval($videoStyle['videoWidth']);
        $height = intval($videoStyle['videoHeight']);
                
        $videoWidth = self::VIDEO_WIDTH;
        $videoHeight = $videoWidth / $width * $height;
        
        return $videoHeight > self::VIDEO_HEIGHT;
    }


    // 是否超清, [2022-1-3]暂时这样写
    private static function isHdv () {
        return false;
    }


    // 入口
    public static function handle ($editor_data, $uid, $tid) {

        // 用户权限表
        $userRole = OldUserRoleModel::where('pid', $uid)
            ->where('tids', $tid)
            ->find();


        $is_XHeight = false;    // 超高
        $is_Hgif = false;       // 动图
        $is_video = false;      // 横版视频
        $is_Hvideo = false;     // 竖版视频
        $is_hdv = false;        // 超清
        if ($userRole) {
            $is_XHeight = ( $userRole->is_height3w && $userRole->h3_time > time() );
            $is_Hgif    = ( $userRole->is_gif && $userRole->g_time > time() );
            $is_video   = ( $userRole->is_video && $userRole->v_time > time() );
            $is_Hvideo  = ( $userRole->is_hvideo && $userRole->hv_time > time() );
            $is_hdv     = ( $userRole->is_hdv && $userRole->hdv_time > time() );
        }


        // 总高度
        $height_count = 0;
        foreach ($editor_data as $item) {

            $mk_type = $item['mk_type'];
            $groups = $item['groups'];
            $boxStyle = $item['boxStyle'];

            // 是否有动图权限
            if ($mk_type === self::MK_TYPE_GIF) {

                // 没有任何动图权限
                if (!$is_Hgif) return 'AUTH_NOTGIF_ERR';
                
                // 动图是否超高
                // $gif_component = array_pop($groups);
                // if (self::isGifXheight($gif_component)) {}
            }

            // 是否有视频权限
            if ($mk_type === self::MK_TYPE_VIDEO) {

                // 没有任何视频权限
                if (!$is_video && !$is_Hvideo) return 'AUTH_NOTVIDEO_ERR';
                
                // 视频是否超高
                $video_component = array_pop($groups);
                if (self::isVideoXHeight($video_component)) {
                    if (!$is_Hvideo) return 'AUTH_HVIDEO_ERR';
                }
            }

            // 是否有超清权限
            if (self::isHdv()) {
                if (!$is_hdv) return 'AUTH_HDV_ERR';
            }

            // 累计高度
            $height = intval($boxStyle['height']);
            $height_count = $height_count + $height;

            // 是否存在视频链接的热区, 并且看是否有权限
            foreach ($groups as $item) {
                $group_type = $item['type'];
                if ($group_type === 'hotspot') {
                    $link = $item['hotspotStyle']['link'];
                    preg_match('/\.mp4$/', $link, $p);
                    if (!empty($p)) {
                        if (!$is_video && !$is_Hvideo) return 'AUTH_VIDEO_LINK_ERR';
                    }
                }
            }
        }
        
        // 超高
        if ($height_count > self::X_HEIGHT) {
            // 没有超高权限
            if (!$is_XHeight) return 'AUTH_XHEIGHT_ERR';
        }

        return true;
    }
}