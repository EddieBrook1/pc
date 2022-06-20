<?php
namespace app\controller\tb\xiangqing;


use think\facade\Validate;
use app\extend\CssValidator;

class SyncExecValidator {

    // 允许的资源类型
    private const RESOURCE_TYPE_ACCEPT = [
        'normal',
        'video',
        'gif',
    ];

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
        if (self::isHasGif($editor_data) && self::isHasVideo($editor_data)) return '不允许同时含有动图和视频';

        // 检查resource_object格式是否可用
        $is_resource_object_ok = self::isResourceObjectOk($resource_object);
        if ($is_resource_object_ok !== true) $is_resource_object_ok;

        return true;
    }
}