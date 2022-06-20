<?php
namespace app\extend\tb;


// 旺铺格式的数据转文本编辑格式
class TbWangpuToWenBen {

    // 单个图片的html
    private const IMG_HTML = '
        <div style="width:{{width}};height:{{height}};font-size: 0.0px;overflow: hidden;">
            <img border="0" src="{{url}}" style="width: 100.0%;" usemap="#{{map_id}}" />
            <map name="{{map_id}}" id="{{map_id}}">{{area}}</map>
        </div>
    ';

    // 热区html
    private const AREA_HTML = '<area coords="{{link_pos}}" href="{{link}}" shape="rect" />';


    // 生成groupId与热区数据的映射, 返回一个数组, 键名是groupId, 键值是一个数组, 数组每一项是热区数据
    private function buildHotspotMap ($templateContent) {

        $hotspot_type = 'hyperlink';
        $result = [];

        foreach ($templateContent['groups'] as $item) {

            $groupId = $item['groupId'];
            $components = $item['components'];
            foreach ($components as $component) {

                // 不是热区跳过
                if ($component['componentType'] !== $hotspot_type) continue;
                
                // 记录下来
                if (!isset($result[$groupId])) $result[$groupId] = [];
                array_push($result[$groupId], $component);
            }
        }

        return $result;
    }


    // 入口
    public function handle ($detailParam, $templateContent) {

        // 提取出模版数据, 键名为groupId; 键值为热区数组, 如果有

        $hotspot_map = $this->buildHotspotMap($templateContent);

        $result = '';
        $map_id = time();
        $pic_data = $detailParam['params'];
        $template_data = $templateContent['groups'];

        foreach ($pic_data as $item) {

            $width = $item['width'] . 'px';
            $height = $item['height'] . 'px';
            $url = $item['imageUrls'][0];
            $groupId = $item['groupId'];
            
            // 生成一份图片html
            $img_html = self::IMG_HTML;
            $img_html = str_replace('{{width}}', $width, $img_html);
            $img_html = str_replace('{{height}}', $height, $img_html);
            $img_html = str_replace('{{url}}', $url, $img_html);
            $img_html = str_replace('{{map_id}}', $map_id, $img_html);

            // 生成链接html, 如果有
            $area_html = '';
            if (isset($hotspot_map[$groupId])) {

                $hotspots = $hotspot_map[$groupId];
                foreach ($hotspots as $hotspot) {

                    $hotspot_boxStyle = $hotspot['boxStyle'];
                    $hotspot_width    = $hotspot_boxStyle['width'];
                    $hotspot_height   = $hotspot_boxStyle['height'];
                    $hotspot_top      = $hotspot_boxStyle['top'];
                    $hotspot_left     = $hotspot_boxStyle['left'];
                    $hotspot_link     = $hotspot['params']['link'];

                    // 左上角的点
                    $left1 = $hotspot_left;
                    $top1 = $hotspot_top;

                    // 右下角的点
                    $left2 = $hotspot_left + $hotspot_width;
                    $top2 = $hotspot_top + $hotspot_height;

                    $link_pos = "$left1, $top1, $left2, $top2";

                    $area_html_item = self::AREA_HTML;
                    $area_html_item = str_replace('{{link_pos}}', $link_pos, $area_html_item);
                    $area_html_item = str_replace('{{link}}', $hotspot_link, $area_html_item);

                    $area_html = $area_html . $area_html_item;
                }
            }

            $img_html = str_replace('{{area}}', $area_html, $img_html);
            $result = $result . $img_html;

            $map_id += 1;
        }

        return $result;
    }
}