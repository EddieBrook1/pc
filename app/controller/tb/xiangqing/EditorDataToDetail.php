<?php
namespace app\controller\tb\xiangqing;


// 新的格式转换成表数据
class EditorDataToDetail {

    // 模块类型
    private const MK_TYPE_NORMAL = 'normal';    // 普通模块
    private const MK_TYPE_GIF    = 'gif';       // 动图模块
    private const MK_TYPE_VIDEO  = 'video';     // 视频模块

    // group类型
    private const GROUP_TYPE_PIC     = 'pic';       // 图片组件
    private const GROUP_TYPE_TEXT    = 'text';      // 文字组件
    private const GROUP_TYPE_HOTSPOT = 'hotspot';   // 热区
    private const GROUP_TYPE_GIF     = 'gif';       // 动图组件
    private const GROUP_TYPE_VIDEO   = 'video';     // 视频组件


    /**
     * 提取热区数据
     * 数据表中的格式, template字段存图片组件和文字组件, 其他东西存在别的字段
     * 热区存在一个字段, 因此这个方法的作用是把热区从template数据中取出来转成热区字段需要的格式
     * 
     * @param [Array] $editor_data
     * @return Array ['hotspots' => $hotspots, 'editor_data' => $editor_data]
     */
    private static function extractHotspot ($editor_data) {

        $hotspots = [];
        foreach ($editor_data as $mk_index => &$mk) {
            $groups = $mk['groups'];
            $groups_newly = [];

            foreach ($groups as $group) {

                if ($group['type'] !== self::GROUP_TYPE_HOTSPOT) {
                    // 不是热区

                    array_push($groups_newly, $group);
                } else {
                    // 是热区

                    $boxStyle = $group['boxStyle'];
                    $link = $group['hotspotStyle']['link'];
    
                    $width = floatval($boxStyle['width']);
                    $height = floatval($boxStyle['height']);
                    $top = floatval($boxStyle['top']);
                    $left = floatval($boxStyle['left']);
    
                    $hotspots_item = [
                        $mk_index,
                        $width,
                        $height,
                        $left,
                        $top,
                        $link
                    ];

                    array_push($hotspots, $hotspots_item);
                }
            }

            $mk['groups'] = $groups_newly;
        }


        return [
            'hotspots' => $hotspots,
            'editor_data' => $editor_data,
        ];
    }


    // 入口
    public static function handle ($editor_data) {

        $template = [];
        $hotspot = [];
        $gif = [];
        $video = [];

        // 提取热区
        [
            'hotspots' => $hotspots,
            'editor_data' => $editor_data,
        ] = self::extractHotspot($editor_data);

        // 自定义id, 动图数据要用到
        $custom_id = 0;
        
        // 剔除动图模块和视频模块和mk_type字段后的数据
        $editor_data_newly = [];

        // 提取动图和视频
        foreach ($editor_data as $index => $mk) {

            // 剔除 hotspotStyle、gifStyle、videoStyle后其他字段
            $groups_newly = [];

            $mk_type = $mk['mk_type'];
            $boxStyle = $mk['boxStyle'];
            $groups = $mk['groups'];
            if ($mk_type === self::MK_TYPE_GIF) {
                // 动图模块
                
                $mk_height = $boxStyle['height'];
                $gifStyle = array_pop($groups)['gifStyle'];
                $src = $gifStyle['src'];
                $first_frame = 0;
                
                $gif_item = [
                    $index,
                    $mk_height,
                    $src,
                    $custom_id,
                    $first_frame,
                    $src
                ];

                $custom_id += 1;
                array_push($gif, $gif_item);

            } else if ($mk_type === self::MK_TYPE_VIDEO) {
                // 视频模块

                $mk_height = $boxStyle['height'];
                $videoStyle = array_pop($groups)['videoStyle'];
                $playUrl = $videoStyle['playUrl'];

                $type = $videoStyle['type'];
                if ($type == 'height_fixed') {
                    $type = 1;
                }
                if ($type == 'hegiht_auto') {
                    $type = 0;
                }

                $videos_item = [
                    $index,
                    $mk_height,
                    $playUrl,
                    $playUrl,
                    $type,
                    self::GROUP_TYPE_VIDEO,
                    json_encode($videoStyle)
                ];
                
                // 历史遗留原因, 视频数据保存在动图字段里, 见DetailToEditorData注释
                array_push($gif, $videos_item);

            } else {
                // 普通模块

                // 剔除其他字段
                foreach ($groups as $group) {
                    unset($group['hotspotStyle']);
                    unset($group['gifStyle']);
                    unset($group['videoStyle']);

                    array_push($groups_newly, $group);
                }

                $mk['groups'] = $groups_newly;
                unset($mk['mk_type']);
                array_push($editor_data_newly, $mk);
            }
        }

        
        return [
            'template' => json_encode($editor_data_newly),
            'hotspot' => json_encode($hotspots),
            'gif' => json_encode($gif),
            'video' => json_encode($video),
        ];
    }
}