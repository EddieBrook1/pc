<?php
namespace app\controller\tb;

use app\BaseController;
use think\Request;
use think\facade\Validate;
use think\facade\Queue;

use app\model\SyncMainJobModel;
use app\model\UploadModel;
use app\extend\Util;

class Sync extends BaseController {

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
    private function buildUploadJobPayloads ($main_job_id, $goods_id, $pics) {

        $result = [];
        foreach ($pics as $item) {
            
            if ($item['type'] == 'origin') continue;
            array_push($result, [
                // 主任务id
                'main_job_id'   => $main_job_id,
                // 图片在数据库的id
                'local_id'        => $item['local_id'],
                // 图片本地地址
                'local_path'      => null,
                // 商品id
                'goods_id'      => $goods_id,
            ]);
        }

        // 填充本地地址, 后面消费job时, 可以稍微增速
        $local_paths = UploadModel::whereIn('id', array_column($result, 'local_id'))->column('local_path', 'id');
        foreach ($result as &$item) {

            $item['local_path'] = $local_paths[$item['local_id']];
        }

        return $result;
    }


    // 电脑端同步
    public function pc (Request $request) {

        // 验证入参
        $input = $request->post();
        if (!SyncPcValidator::handle($input)) return $this->resultJson([], false, 'PARAM_ERR');
        
        // 用户uid
        $uid = 113;
        // 商品id
        $goods_id = $input['goods_id'];
        // 模版id
        $template_id = $input['template_id'];
        // 编辑器数据
        $editor_data = json_decode($input['editor_data'], true);
        // 图片数据
        $pics = json_decode($input['pics'], true);


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
            'editor_data'   => json_encode($editor_data, 320),
            // 要上传的图片, 包括原图等信息
            'pics'          => json_encode($pics, 320),
            // 默认状态为正在处理模版数据...
            'status_code'   => SyncMainJobModel::SYNC_PROCESSING_DATA,
            // 任务进度
            'progress'      => SyncMainJobModel::buildProgress($pics),
            // 任务创建时间
            'create_time'   => time(),
            // 任务结束时间
            'done_time'     => 0
        ]);

        
        // 创建子任务-上传图片
        $upload_payload_arr = $this->buildUploadJobPayloads($syncMainJob->id, $goods_id, $pics);  // 负载数据
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
        return $this->resultJson([], true, 'SYNC_SUBMIT_SUCC');
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

        $main_job_id = $input['main_job_id'];
        $syncMainJob = SyncMainJobModel::find($main_job_id);

        // 任务不存在
        if ($syncMainJob == null) return $this->resultJson([], false, 'PARAM_ERR');

        return $this->resultJson([
            'status_code' => $syncMainJob->status_code
        ], true);
    }


    // 获取一段时间内的同步记录
    public function history (Request $request) {

        // 未登录, 返回空数组
        // if (!Util::isLogin()) return $this->resultJson([], true);
        // $uid = Util::getLoggedUid();

        $uid = 113;
        $history_expire = config('sync.history_expire');
        $time = time() - $history_expire;

        $data = SyncMainJobModel::where('uid', $uid)
            ->where('create_time', '>', $time)
            ->select()
            ->toArray();

        return $this->resultJson($data, true);
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
}


class SyncPcValidator {

    /**
     * 检查pics是否可用
     * 
     *
     * @param [String] $pics 图片数据的json格式
     * @return boolean
     */
    private static function isPicsOk ($pics_raw) {

        // 尝试转成数组
        try {
            
            $pics = json_decode($pics_raw, true);
        } catch (\Throwable $th) {

            $pics = null;
        }
        if ($pics == null) return false;

        // 如果没有这几个键返回错误
        foreach ($pics as $item) {

            if (
                !array_key_exists('width',  $item) ||
                !array_key_exists('height', $item) ||
                !array_key_exists('type',   $item) ||
                !array_key_exists('url',    $item)
            ) return false;
        }

        // 检查内部格式
        $validate = Validate::rule([
            'width' => 'require|number',
            'height' => 'require|number',
            'type' => 'require|in:local,origin',
        ]);
        foreach ($pics as $item) {

            if (!$validate->check($item)) return false;
            
            // 如果是本地图片, 还需要有本地图片id
            if ($item['type'] == 'local') {
                
                // local
                if (!isset($item['local_id'])) return false;
            }
        }
        
        return true;
    }


    public static function handle ($input) {

        $validate = Validate::rule([
            // 商品id
            'goods_id'      => 'require',
            // 模版id
            'template_id'   => 'require',
            // 模版数据
            'editor_data'   => 'require',
            // 待上传的图片
            'pics'          => 'require',
        ]);
        if (!$validate->check($input)) return false;

        $editor_data = $input['editor_data'];
        $pics = $input['pics'];

        // 检查editor_data是否可用
        if (!EditorDataValidator::handle($editor_data)) return false;

        // 检查pics格式是否可用
        if (!self::isPicsOk($pics)) return false;

        return true;
    }
}


class EditorDataValidator {
    
    // 最外层检查
    private static function check1 ($editor_data) {

        // 必须有这三个键
        foreach ($editor_data as $item) {

            if (
                !array_key_exists('boxStyle', $item) ||
                !array_key_exists('groups', $item) ||
                !array_key_exists('category', $item)
            ) return false;
        }

        $validate = Validate::rule([
            'boxStyle' => 'require|array',
            'groups' => 'require|array',
        ]);
        foreach ($editor_data as $item) {
            
            if (!$validate->check($item)) return false;
        }

        return true;
    }


    // 检查模块的boxStyle
    private static function check2 ($editor_data) {

        $mk_boxStyles = array_column($editor_data, 'boxStyle');
        $validate = Validate::rule([
            'background-color' => 'require',
            'background-image' => 'require',
            'background-repeat' => 'require',
            'width' => 'require',
            'height' => 'require',
        ]);
        foreach ($mk_boxStyles as $item) {

            if (!$validate->check($item)) return false;
        }

        return true;
    }


    // 检查所有的groups
    private static function check3 ($editor_data) {

        // 取出所有的 groups
        $_all_groups = array_column($editor_data, 'groups');
        
        // 合并成一个数组
        $all_groups = [];
        foreach ($_all_groups as $groups) {
            foreach ($groups as $item) {
                array_push($all_groups, $item);
            }
        }

        // groups中没有这些键名返回错误
        foreach ($all_groups as $groups) {
            if (
                !array_key_exists('boxStyle',     $groups) ||
                !array_key_exists('gifStyle',     $groups) ||
                !array_key_exists('hotspotStyle', $groups) ||
                !array_key_exists('picStyle',     $groups) ||
                !array_key_exists('textStyle',    $groups) ||
                !array_key_exists('type',         $groups) ||
                !array_key_exists('videoStyle',   $groups)
            ) return false;
        }

        // 检查每项的boxStyle字段
        if (!self::check3BoxStyle($all_groups)) return false;

        // 检查每一项的样式字段
        foreach ($all_groups as $item) {

            $type = $item['type'];
            $isOk = true;
            switch ($type) {
                case 'pic':
                    $isOk = self::check3PicStyle($item['picStyle']);
                    break;
                case 'text':
                    $isOk = self::check3TextStyle($item['textStyle']);
                    break;
                case 'hotspot':
                    $isOk = self::check3HotspotStyle($item['hotspotStyle']);
                    break;
                case 'gif':
                    $isOk = self::check3GifStyle($item['gifStyle']);
                    break;
                case 'video':
                    $isOk = self::check3VideoStyle($item['videoStyle']);
                    break;
            }

            if (!$isOk) return false;
        }


        return true;
    }


    // 检查groups的boxStyle字段
    private static function check3BoxStyle ($all_groups) {

        // groups['boxStyle']中没有这些字段返回错误
        $groups_boxStyles = array_column($all_groups, 'boxStyle');
        foreach ($groups_boxStyles as $item) {
            if (
                !array_key_exists('width',          $item) ||
                !array_key_exists('height',         $item) ||
                !array_key_exists('top',            $item) ||
                !array_key_exists('left',           $item) ||
                !array_key_exists('z-index',        $item) ||
                !array_key_exists('border-radius',  $item) ||
                !array_key_exists('guding',         $item) ||
                !array_key_exists('biaoji',         $item) ||
                !array_key_exists('type',           $item)
            ) return false;
        }

        return true;
    }


    // 图片组件样式检查
    private static function check3PicStyle ($picStyle) {

        $validate = Validate::rule([
            'width'     => 'require',
            'height'    => 'require',
            'top'       => 'require',
            'left'      => 'require',
            'src'       => 'require',
            'scaling_width'  => 'require',
            'scaling_height' => 'require',
        ]);
        if (!$validate->check($picStyle)) return false;

        return true;
    }
    
    
    // 文字组件样式检查
    private static function check3textStyle ($textStyle) {

        $validate = Validate::rule([
            'font-size'         => 'require',
            'font-family'       => 'require',
            'color'             => 'require',
            'text-align'        => 'require',
            'font-weight'       => 'require',
            'background-color'  => 'require',
            'value'             => 'require',
            'font-style'        => 'require',
            'letter-spacing'    => 'require',
            'line-height'       => 'require',
        ]);
        if (!$validate->check($textStyle)) return false;

        return true;
    }
    

    // 热区组件样式检查
    private static function check3HotspotStyle ($hotspotStyle) {

        $validate = Validate::rule([
            'link' => 'require'
        ]);
        if (!$validate->check($hotspotStyle)) return false;

        return true;
    }
    

    // 动图组件样式检查
    private static function check3GifStyle ($gifStyle) {

        return true;
    }
    

    // 视频组件样式检查
    private static function check3VideoStyle ($videoStyle) {

        return true;
    }


    // 入口
    public static function handle ($editor_data) {

        // 尝试转成数组
        try {

            $editor_data = json_decode($editor_data, true);
        } catch (\Throwable $th) {

            $editor_data = null;
        }
        if ($editor_data == null) return false;

        if (!self::check1($editor_data)) return false;
        if (!self::check2($editor_data)) return false;
        if (!self::check3($editor_data)) return false;

        return true;
    }
}