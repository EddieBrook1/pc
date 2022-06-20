<?php
namespace app\extend;

use app\extend\CssValidator;

/**
 * 模块数据验证器
 * 示例数据:
    [[
        'boxStyle' => [
            "width" => "750px",
            "height" => "420px",
            "background-color" => "rgb(255, 255, 255)",
            "background-image" => "none",
            "background-repeat" => "repeat",
        ],
        'groups' => [[
            'boxStyle' => [
                'width' => '750px',
                'height' => '750px',
                'top' => '0px',
                'left' => '0px',
                'z-index' => '1',
                'border-radius' => '0px',
                'guding' => '0',
                'biaoji' => '0',
                'type' => '',
            ],
            'picStyle' => [
                'width' => '10px',
                'height' => '10px',
                'top' => '0px',
                'left' => '0px',
                'scaling_width' => 10,
                'scaling_height' => 10,
                'src' => 'xxx.jpg'
            ],
            'textStyle' => [
                "font-size" => "65px",
                "font-family" => "simsun",
                "color" => "rgb(154, 26, 26)",
                "text-align" => "left",
                "font-weight" => "normal",
                "background-color" => "rgba(0, 0, 0, 0)",
                "value" => "10",
                "font-style" => "normal",
                "letter-spacing" => "0px",
                "line-height" => "1.5",
                'writing-mode' => 'vertical-rl'
            ],
            'hotspotStyle' => [
                'link' => 'abc',
            ],
            'gifStyle' => [
                'src' => 'abc',
            ],
            'videoStyle' => [
                id: ""
                length: 16
                name: "xxx"
                playUrl: "https://xxxx.mp4"
                snapshot: "https://xxx.jpg"
                type: "height_auto"
                videoHeight: "xxxpx"
                videoWidth: "xxxpx"
            ],
            'type' => 'video',
        ]],
        'category' => 0,
        'mk_type' => 'normal',
    ]]
 * 其中groups里, picStyle、textStyle、hotspotSytle, gifStyle, videoStyle根据type保证对应项有数据即可, 其他留空, 这里为方便调试, 全部都写上去了。
 * 
 */
class EditorDataValidator {

    // 验证模块的boxStyle是否可用
    private static function isMkBoxStyleOk ($index, $boxStyle) {

        // 允许的css样式
        $boxStyle_accept = [
            'width',
            'height',
            'background-color',
            'background-image',
            'background-repeat',
        ];

        // 缺少字段
        $keys = array_keys($boxStyle);
        foreach ($boxStyle_accept as $field) {
            if (!in_array($field, $keys)) return "模块[$index]['boxStyle']不可用";
        }

        $width = $boxStyle['width'];
        $height = $boxStyle['height'];
        $backgroundColor = $boxStyle['background-color'];
        $backgroundImage = $boxStyle['background-image'];
        $backgroundRepeat = $boxStyle['background-repeat'];

        // 验证字段值是否满足要求
        if (!CssValidator::isWidth($width))                         return "模块[$index]['boxStyle']['width']不可用";
        if (!CssValidator::isHeight($height))                       return "模块[$index]['boxStyle']['height']不可用";
        if (!CssValidator::isBackgroundColor($backgroundColor))     return "模块[$index]['boxStyle']['background-color']不可用";
        if (!CssValidator::isBackgroundImage($backgroundImage))     return "模块[$index]['boxStyle']['background-image']不可用";
        if (!CssValidator::isBackgroundRepeat($backgroundRepeat))   return "模块[$index]['boxStyle']['background-repeat']不可用";

        return true;
    }


    // 验证 group['boxStyle'] 是否可用
    private static function isGroupBoxStyleOk ($mk_index, $index, $boxStyle) {

        // 允许css字段
        $accept_css = [
            'width',
            'height',
            'top',
            'left',
            'z-index',
            'border-radius',
            'guding',
            'biaoji',
            'type',
        ];

        // 缺少字段
        $keys = array_keys($boxStyle);
        foreach ($accept_css as $field) {
            if (!in_array($field, $keys)) return "模块[$mk_index]['groups'][$index]['boxStyle']不可用";
        }

        $width        = $boxStyle['width'];
        $height       = $boxStyle['height'];
        $top          = $boxStyle['top'];
        $left         = $boxStyle['left'];
        $zIndex       = $boxStyle['z-index'];
        $borderRadius = $boxStyle['border-radius'];
        $guding       = $boxStyle['guding'];
        $biaoji       = $boxStyle['biaoji'];
        $type         = $boxStyle['type'];

        if (!CssValidator::isWidth($width))         return "模块[$mk_index]['groups'][$index]['boxStyle']['width']不可用";
        if (!CssValidator::isHeight($height))       return "模块[$mk_index]['groups'][$index]['boxStyle']['height']不可用";
        if (!CssValidator::isTop($top))             return "模块[$mk_index]['groups'][$index]['boxStyle']['top']不可用";
        if (!CssValidator::isLeft($left))           return "模块[$mk_index]['groups'][$index]['boxStyle']['left']不可用";

        if (!is_numeric($zIndex) && !is_string($zIndex)) {
            return "模块[$mk_index]['groups'][$index]['boxStyle']['z-index']不可用";
        } else {
            if (!is_numeric($zIndex)) {
                if (is_string($zIndex) && $zIndex !== 'auto') {
                    return "模块[$mk_index]['groups'][$index]['boxStyle']['z-index']不可用";
                }
            }
        }

        preg_match('/^[1-9]?[0-9]*([\.][0-9]{1,})?%$/', $borderRadius, $p);
        if (!CssValidator::isPxSize($borderRadius) && empty($p)) return "模块[$mk_index]['groups'][$index]['boxStyle']['border-radius']不可用";
        if (!is_numeric($guding))                   return "模块[$mk_index]['groups'][$index]['boxStyle']['guding']不可用";
        if (!is_numeric($biaoji))                   return "模块[$mk_index]['groups'][$index]['boxStyle']['biaoji']不可用";
        if (!is_string($type))                      return "模块[$mk_index]['groups'][$index]['boxStyle']['type']不可用";

        return true;
    }

    
    // 验证 gorup['picStyle'] 是否可用
    private static function isGroupPicStyleOk ($mk_index, $index, $picStyle) {

        // 允许的字段
        $accept_field = [
            'width',
            'height',
            'top',
            'left',
            'scaling_width',
            'scaling_height',
            'src',
        ];

        // 缺少字段
        $keys = array_keys($picStyle);
        foreach ($accept_field as $field) {
            if (!in_array($field, $keys)) return "模块[$mk_index]['groups'][$index]['picStyle']不可用";
        }

        $width          = $picStyle['width'];
        $height         = $picStyle['height'];
        $top            = $picStyle['top'];
        $left           = $picStyle['left'];
        $scaling_width  = $picStyle['scaling_width'];
        $scaling_height = $picStyle['scaling_height'];
        $src            = $picStyle['src'];
        
        if (!CssValidator::isWidth($width))     return "模块[$mk_index]['groups'][$index]['picStyle']['width']不可用";
        if (!CssValidator::isHeight($height))   return "模块[$mk_index]['groups'][$index]['picStyle']['height']不可用";
        if (!CssValidator::isLeft($left))       return "模块[$mk_index]['groups'][$index]['picStyle']['left']不可用";
        if (!CssValidator::isTop($top))         return "模块[$mk_index]['groups'][$index]['picStyle']['top']不可用";
        if (!is_numeric($scaling_width))        return "模块[$mk_index]['groups'][$index]['picStyle']['scaling_width']不可用";
        if (!is_numeric($scaling_height))       return "模块[$mk_index]['groups'][$index]['picStyle']['scaling_height']不可用";
        if (!is_string($src))                   return "模块[$mk_index]['groups'][$index]['picStyle']['src']不可用";

        return true;
    }


    // 验证group['textStyle'] 是否可用
    private static function isGroupTextStyleOk ($mk_index, $index, $textStyle) {

        // 允许的字段
        $accept_field = [
            'font-size',
            'font-family',
            'color',
            'text-align',
            'font-weight',
            'background-color',
            'value',
            'font-style',
            'letter-spacing',
            'line-height',
            'writing-mode',
        ];

        // 缺少字段
        $keys = array_keys($textStyle);
        foreach ($accept_field as $field) {
            if (!in_array($field, $keys)) return "模块[$mk_index]['groups'][$index]['textStyle']不可用";
        }

        $fontSize        = $textStyle['font-size'];
        $fontFamily      = $textStyle['font-family'];
        $color           = $textStyle['color'];
        $textAlign       = $textStyle['text-align'];
        $fontWeight      = $textStyle['font-weight'];
        $backgroundColor = $textStyle['background-color'];
        $value           = $textStyle['value'];
        $fontStyle       = $textStyle['font-style'];
        $letterSpacing   = $textStyle['letter-spacing'];
        $lineHeight      = $textStyle['line-height'];
        $writingMode     = $textStyle['writing-mode'];

        if (!CssValidator::isPxSize($fontSize))                 return "模块[$mk_index]['groups'][$index]['textStyle']['font-size']不可用";
        if (!is_string($fontFamily))                            return "模块[$mk_index]['groups'][$index]['textStyle']['font-family']不可用";
        if (!CssValidator::isColor($color))                     return "模块[$mk_index]['groups'][$index]['textStyle']['color']不可用";
        if (!CssValidator::isTextAlign($textAlign))             return "模块[$mk_index]['groups'][$index]['textStyle']['text-align']不可用";
        if (!CssValidator::isFontWeight($fontWeight))           return "模块[$mk_index]['groups'][$index]['textStyle']['font-weight']不可用";
        if (!CssValidator::isBackgroundColor($backgroundColor)) return "模块[$mk_index]['groups'][$index]['textStyle']['background-color']不可用";
        if (!is_string($value))                                 return "模块[$mk_index]['groups'][$index]['textStyle']['value']不可用";
        if (!CssValidator::isFontStyle($fontStyle))             return "模块[$mk_index]['groups'][$index]['textStyle']['font-style']不可用";
        if (!CssValidator::isPxSize($letterSpacing))            return "模块[$mk_index]['groups'][$index]['textStyle']['letter-spacing']不可用";
        if (!CssValidator::isPxSize($lineHeight) && !is_numeric($lineHeight)) return "模块[$mk_index]['groups'][$index]['textStyle']['line-height']不可用";
        if (!CssValidator::isWritingMode($writingMode))         return "模块[$mk_index]['groups'][$index]['textStyle']['writing-mode']不可用";

        return true;
    }


    // 验证group['hotspotStyle'] 是否可用
    private static function isHotspotStyleOk ($mk_index, $index, $hotspotStyle) {

        // 允许的字段
        $accept_field = [ 'link' ];

        // 缺少字段
        $keys = array_keys($hotspotStyle);
        foreach ($accept_field as $field) {
            if (!in_array($field, $keys)) return "模块[$mk_index]['groups'][$index]['hotspotStyle']不可用";
        }

        $link = $hotspotStyle['link'];
        if (!is_string($link)) return "模块[$mk_index]['groups'][$index]['hotspotStyle']['link']不可用";

        return true;
    }


    // 验证group['gifStyle'] 是否可用
    private static function isGifStyleOk ($mk_index, $index, $gifStyle) {

        // 允许的字段
        $accept_field = [ 'src' ];

        // 缺少字段
        $keys = array_keys($gifStyle);
        foreach ($accept_field as $field) {
            if (!in_array($field, $keys)) return "模块[$mk_index]['groups'][$index]['gifStyle']不可用";
        }

        $src = $gifStyle['src'];
        if (!is_string($src)) return "模块[$mk_index]['groups'][$index]['gifStyle']['src']不可用";

        return true;
    }


    // 验证group['videoStyle'] 是否可用
    private static function isVideoStyleOk ($mk_index, $index, $videoStyle) {

        // 允许的字段
        $accept_field = [
            'id',
            'length',
            'name',
            'playUrl',
            'snapshot',
            'type',
            'videoWidth',
            'videoHeight',
        ];

        // 视频类型允许字段
        $video_type_accept = ['height_fixed', 'height_auto'];

        // 缺少字段
        $keys = array_keys($videoStyle);
        foreach ($accept_field as $field) {
            if (!in_array($field, $keys)) return "模块[$mk_index]['groups'][$index]['videoStyle']不可用";
        }

        $id = $videoStyle['id'];
        $length = $videoStyle['length'];
        $name = $videoStyle['name'];
        $playUrl = $videoStyle['playUrl'];
        $snapshot = $videoStyle['snapshot'];
        $type = $videoStyle['type'];
        $videoWidth = $videoStyle['videoWidth'];
        $videoHeight = $videoStyle['videoHeight'];

        if (!is_numeric($id)) return "模块[$mk_index]['groups'][$index]['videoStyle']['id']不可用";
        if (!is_numeric($length)) return "模块[$mk_index]['groups'][$index]['videoStyle']['length']不可用";
        if (!is_string($name)) return "模块[$mk_index]['groups'][$index]['videoStyle']['name']不可用";
        if (!is_string($playUrl)) return "模块[$mk_index]['groups'][$index]['videoStyle']['playUrl']不可用";
        if (!is_string($snapshot)) return "模块[$mk_index]['groups'][$index]['videoStyle']['snapshot']不可用";
        if (!in_array($type, $video_type_accept)) return "模块[$mk_index]['groups'][$index]['videoStyle']['type']不可用";
        if (!CssValidator::isPxSize($videoWidth)) return "模块[$mk_index]['groups'][$index]['videoStyle']['videoWidth']不可用";
        if (!CssValidator::isPxSize($videoHeight)) return "模块[$mk_index]['groups'][$index]['videoStyle']['videoHeight']不可用";

        return true;
    }


    // 验证模块的groups是否可用
    private static function isMkGroupsOk ($mk_index, $groups) {

        $group_field_accept = [
            'boxStyle',
            'picStyle',
            'textStyle',
            'hotspotStyle',
            'gifStyle',
            'videoStyle',
            'type',
        ];

        $group_type_accept = [
            'pic',
            'text',
            'hotspot',
            'gif',
            'video'
        ];

        foreach ($groups as $index => $group) {

            // 不是数组
            if (!is_array($group)) return "模块[$mk_index]['boxStyle']['groups'][$index]不是数组";

            // 缺少字段
            $keys = array_keys($group);
            foreach ($group_field_accept as $field) {
                if (!in_array($field, $keys)) return "模块[$mk_index]['boxStyle']['groups'][$index]不可用";
            }

            
            $boxStyle = $group['boxStyle'];
            $picStyle = $group['picStyle'];
            $textStyle = $group['textStyle'];
            $hotspotStyle = $group['hotspotStyle'];
            $gifStyle = $group['gifStyle'];
            $videoStyle = $group['videoStyle'];
            $type = $group['type'];

            // 检查type
            if (!in_array($type, $group_type_accept)) return "模块[$mk_index]['boxStyle']['groups'][$index]['type']必须为" . json_encode($group_type_accept) . '其中一项';

            // 除了type, 其他项必须都为数组
            if (
                !is_array($boxStyle) ||
                !is_array($picStyle) ||
                !is_array($textStyle) ||
                !is_array($hotspotStyle) ||
                !is_array($gifStyle) ||
                !is_array($videoStyle)
            ) return "模块[$mk_index]['boxStyle']['groups'][$index]['xxxStyle']必须为数组";

            // 检查boxStyle
            $isOk = self::isGroupBoxStyleOk($mk_index, $index, $boxStyle);
            if ($isOk !== true) return $isOk;

            
            // 根据type检查对应字段
            switch ($type) {
                case 'pic':
                    $isOk = self::isGroupPicStyleOk($mk_index, $index, $picStyle);
                    break;
                case 'text':
                    $isOk = self::isGroupTextStyleOk($mk_index, $index, $textStyle);
                    break;
                case 'hotspot':
                    $isOk = self::isHotspotStyleOk($mk_index, $index, $hotspotStyle);
                    break;
                case 'gif':
                    $isOk = self::isGifStyleOk($mk_index, $index, $gifStyle);
                    break;
                case 'video':
                    $isOk = self::isVideoStyleOk($mk_index, $index, $videoStyle);
                    break;
            }
            if ($type !== true) return $isOk;
        }

        return true;
    }


    // 验证模块外层字段
    private static function isAllMkOk ($editor_data) {

        // 模块数据允许的字段
        $mk_field_accept = [
            'boxStyle',
            'groups',
            'category',
            'mk_type'
        ];

        // mk_type允许的字段
        $mk_type_accept = [
            'normal',
            'video',
            'gif',
        ];

        foreach ($editor_data as $index => $item) {

            // 不是数组
            if (!is_array($item)) return '模块数据必须是数组';

            // 字段不可用
            $keys = array_keys($item);
            if (count($mk_field_accept) != count($keys)) return '模块字段不合法';
            foreach ($keys as $key) {
                if (!in_array($key, $mk_field_accept)) return '模块字段不合法';
            }


            $boxStyle = $item['boxStyle'];
            $groups = $item['groups'];
            $category = $item['category'];
            $mk_type = $item['mk_type'];

            // boxStyle和groups必须是数组
            if (!is_array($boxStyle) || !is_array($groups)) return '模块的boxStyle和groups必须是数组';

            // category必须是数字
            if (!is_numeric($category)) return '模块的category必须是数值';

            // mk_type是否在允许范围内
            if (!in_array($mk_type, $mk_type_accept)) return '模块的mk_type必须为' . json_encode($mk_type_accept);


            // 验证模块的boxStyle字段
            $isOk = self::isMkBoxStyleOk($index, $boxStyle);
            if ($isOk !== true) return $isOk;
            
            // 验证模块的group字段
            $isOk = self::isMkGroupsOk($index, $groups);
            if ($isOk !== true) return $isOk;
        }

        return true;
    }


    // 入口
    public static function handle ($editor_data) {

        // 是否为数组, 不是数组尝试转换成数组
        try {
            
            $editor_data = is_array($editor_data)
                ? $editor_data
                : json_decode($editor_data, true);
            
        } catch (\Throwable $th) { $editor_data = null; }
        if (!is_array($editor_data)) return '模版数据必须是个数组';

        // 开始验证
        return self::isAllMkOk($editor_data);
    }
}