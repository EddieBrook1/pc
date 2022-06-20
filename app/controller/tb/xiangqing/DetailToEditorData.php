<?php
namespace app\controller\tb\xiangqing;

// 表数据转换成新的格式
/**
 * 【必读】
 * 因历史遗留问题, 视频数据也存进了动图字段里, 且没有专门的字段做区分, 只能看数组长度和第三条数据的后缀来判断
 * 
 * 动图数据数组长度是 6
 * 视频数据数组长度是 5
 * 
 * 动图数据第三条数据的后缀可能是　.gif 和 .mp4;  .gif是不限高动图、.mp4是限高动图
 * 视频数据第三条数据的后缀统一是 .mp4
 * 
 * 限高和不限高, 它们的数据格式分别是:
 * 限高:
    [
        0,                  第几个模块
        '492px',            模块高度
        'https://xxx.mp4',  动图链接
        '1459544',          动图自定义id
        'https://xxx.png'   同步首帧
        'gif'               表名它是个动图
    ]
    * 不限高:
    [
        1,                  第几个模块
        "950px",            模块高度
        "https://xxx.gif",  动图
        0,                  自定义id, 保持唯一即可
        0,                  动图第一帧，如果没有设置为0
        'https://xxx.gif'   与第三条数据一样
    ]
 * 
 * 
 * 视频又分横版视频和竖版视频
 * 横版视频数据的格式是:
    [
        0,                  第几个模块
        '431px',            模块高度
        'https://xxx.mp4',  视频链接
        'https://xxx.jpg',  视频第一帧
        1,                  表示这是一个横版视频
    ]
 * 
 * 竖版视频数据的格式是:
    [
        0,                  第几个模块
        '1608',             模块高度
        'https://xxx.mp4',  视频链接
        'https://xxx.jpg',  视频第一帧
        0,                  表示这是一个竖版视频
    ]
 *
 *
*/
class DetailToEditorData {

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

    // 视频类型
    private const VIDEO_TYPE_FIXED = 'height_fixed';  // 横版视频
    private const VIDEO_TYPE_AUTO = 'height_auto';  // 竖版视频

    // 编辑器默认宽度
    private const EDITOR_WIDTH = '750px';

    // 模块默认数据
    private const DEFAULT_MK = [
        'boxStyle' => [
            'background-color' => "rgb(255, 255, 255)",
            'background-image' => "none",
            'background-repeat' => "no-repeat",
            'height' => "750px",
            'width' => "750px",
        ],
        'category' => 0,
        'groups' => [],
        'mk_type' => 'normal'
    ];


    // group项默认数据
    private const DEFAULT_GROUP = [
        'boxStyle' => [
            // 这4个字段只有图片组件和文字组件时有用
            'border-radius' => "0px",
            'biaoji'        => "1",
            'guding'        => 0,
            'type'          => "",
            
            // 宽高对所有组件适用
            'width'         => "750px",
            'height'        => "750px",

            // left和top对动图和视频组件无意义, 动图模块和视频模块只能有1个组件
            'left'          => "0px",
            'top'           => "0px",

            // z-index对热区无效, 热区始终保持在最上层; 对动图、视频无意义, 动图模块和视频模块只能有1个组件
            'z-index'       => "0",
        ],
        'gifStyle' => [],
        'hotspotStyle' => [],
        'picStyle' => [],
        'textStyle' => [],
        'type' => 'pic',
        'videoStyle' => [],
    ];    

    // 热区默认内容数据
    private const DEFAULT_HOTSPOT_CONTENT = [
        'link' => ''
    ];

    // 动图默认内容数据
    private const DEFAULT_GIF_CONTENT = [
        'src' => '',
        'type' => '',
    ];

    // 视频默认内容数据
    private const DEFAULT_VIDEO_CONTENT = [
        'id' => null, 
        'length' => null, 
        'name' => null, 
        'playUrl' => null, 
        'snapshot' => null, 
        'type' => null, 
        'videoHeight' => null, 
        'videoWidth' => null, 
    ];
    

    // 判断是否为限高动图
    private static function isGifType1 ($gif) {

        // 长度不等于6, 不是动图
        if (count($gif) !== 6) return false;

        // 第6项值是 video , 不是动图
        if ($gif[5] === self::GROUP_TYPE_VIDEO) return false;

        // 第三条数据的后缀不是gif, 不是限高动图
        $link = $gif[2];
        $exploded = explode('.', $link);

        return array_pop($exploded) === 'mp4';
    }


    // 判断是否为不限高动图
    private static function isGifType2 ($gif) {

        // 长度不等于6, 不是动图
        if (count($gif) !== 6) return false;

        // 第6项值是 video , 不是动图
        if ($gif[5] === self::GROUP_TYPE_VIDEO) return false;

        // 第三条数据的后缀不是gif, 不是不限高动图
        $link = $gif[2];
        $exploded = explode('.', $link);

        return array_pop($exploded) === 'gif';
    }


    // 判断这串gif是动图还是视频, 理由同 self::isGif 方法
    private static function isVideo ($gif) {

        // 判断是否为视频
        if (count($gif) !== 7) return false;

        if ($gif[5] !== self::GROUP_TYPE_VIDEO) return false;

        // 第三条数据后缀不是mp4, 不是视频
        $link = $gif[2];
        $exploded = explode('.', $link);

        return array_pop($exploded) === 'mp4';
    }


    // 追加热区数据
    private static function appendHotspot ($template, $hotspots) {

        foreach ($hotspots as $item) {

            $index  = $item[0]; // 属于哪个模块的
            $width  = $item[1];
            $height = $item[2];
            $left   = $item[3];
            $top    = $item[4];
            $link   = $item[5];

            // 盒子
            $boxStyle = array_merge(self::DEFAULT_GROUP['boxStyle'], [
                'width' => $item[1] . 'px',
                'height' => $item[2] . 'px',
                'left' => $item[3] . 'px',
                'top' => $item[4] . 'px',
            ]);

            // 内容
            $hotspotStyle = array_merge(self::DEFAULT_HOTSPOT_CONTENT, [
                'link' => $link
            ]);

            // group项
            $group = array_merge(self::DEFAULT_GROUP, [
                'boxStyle' => $boxStyle,
                'hotspotStyle' => $hotspotStyle,
                'type' => 'hotspot',
            ]);

            // 追加一条热区group
            array_push($template[$index]['groups'], $group);
        }

        return $template;
    }


    // 追加动图数据
    private static function buildGifMk ($template, $gifs) {
        
        // 返回值
        $result = [];

        // 筛选出限高动图和不限高动图
        $gifType1 = [];     // 限高动图
        $gifType2 = [];     // 不限高动图
        foreach ($gifs as $gif) {
            
            // 是否为限高动图
            if (self::isGifType1($gif)) {
                array_push($gifType1, $gif);
            }

            // 是否为不限高动图
            if (self::isGifType2($gif)) {
                array_push($gifType2, $gif);
            }
        }

        // 追加限高动图数据, 保持空模块, 不添加任何组件
        foreach ($gifType1 as $item) {

            $index        = intval($item[0]);   // 位于第几个模块
            $mk_height    = intval($item[1]);   // 模块高度

            // 模块盒子
            $mk_boxStyle = array_merge(self::DEFAULT_MK['boxStyle'], [
                'height' => $mk_height . 'px'
            ]);

            // 模块数据
            $mk = array_merge(self::DEFAULT_MK, [
                'boxStyle' => $mk_boxStyle,
                'mk_type' => self::MK_TYPE_GIF
            ]);

            array_push($result, [
                'index' => $index,
                'mk' => $mk,
            ]);
        }


        // 追加不限高动图数据
        foreach ($gifType2 as $item) {

            $index        = intval($item[0]);   // 位于第几个模块
            $mk_height    = intval($item[1]);   // 模块高度
            $src          = $item[2];   // 动图链接
            $curtom_id    = $item[3];   // 自定义id, 全局唯一即可
            $first_frame  = $item[4];   // 动图首帧
            $type         = $item[5];   // 类型, 此字段只在限高和不限高动图时有用, 这里无所谓

            // 盒子数据
            $boxStyle = array_merge(self::DEFAULT_GROUP['boxStyle'], [
                'width' => self::EDITOR_WIDTH,
                'height' => $mk_height . 'px',
            ]);

            // 内容数据
            $gifStyle = array_merge(self::DEFAULT_GIF_CONTENT, [
                'src' => $src
            ]);

            // group数据
            $group = array_merge(self::DEFAULT_GROUP, [
                'boxStyle' => $boxStyle,
                'gifStyle' => $gifStyle,
                'type' => self::GROUP_TYPE_GIF,
            ]);


            // 模块盒子
            $mk_boxStyle = array_merge(self::DEFAULT_MK['boxStyle'], [
                'height' => $mk_height . 'px'
            ]);

            // 模块数据
            $mk = array_merge(self::DEFAULT_MK, [
                'boxStyle' => $mk_boxStyle,
                'groups' => [$group],
                'mk_type' => self::MK_TYPE_GIF
            ]);

            array_push($result, [
                'index' => $index,
                'mk' => $mk,
            ]);
        }
        
        return $result;
    }


    // 通过gif字段的数据追加视频数据
    private static function buildVideoMkByGifs ($template, $gifs) {

        // 筛选出动图数据
        $data = [];
        foreach ($gifs as $gif) {
            if (self::isVideo($gif)) {
                array_push($data, $gif);
            }
        }


        // 追加数据
        $result = [];
        foreach ($data as $item) {

            $index        = intval($item[0]);   // 位于第几个模块
            $mk_height    = intval($item[1]);   // 模块高度
            $src          = $item[2];           // 视频链接
            $first_frame  = $item[3];           // 视频首帧
            $video_type   = intval($item[4]) === 1
                ? self::VIDEO_TYPE_FIXED            // 横版视频
                : self::VIDEO_TYPE_AUTO;           // 竖版视频


            // 盒子数据
            $boxStyle = array_merge(self::DEFAULT_GROUP['boxStyle'], [
                'width' => self::EDITOR_WIDTH,
                'height' => $mk_height . 'px',
            ]);

            // 内容数据
            if (isset($item[5]) && $item[5] === self::GROUP_TYPE_VIDEO) {

                try {
                    $_videoStyle = json_decode($item[6], true);
                } catch (\Throwable $th) {
                    $_videoStyle = [];
                    RecordError::handle(
                        __CLASS__,
                        $th->getMessage(),
                        '表数据转换成新的格式视频格式转换失败'
                    );
                }
                
                $videoStyle = array_merge(self::DEFAULT_VIDEO_CONTENT, $_videoStyle);
            } else {
                $videoStyle = array_merge(self::DEFAULT_VIDEO_CONTENT, []);
            }


            // group数据
            $group = array_merge(self::DEFAULT_GROUP, [
                'boxStyle' => $boxStyle,
                'videoStyle' => $videoStyle,
                'type' => self::GROUP_TYPE_VIDEO,
            ]);


            // 模块盒子
            $mk_boxStyle = array_merge(self::DEFAULT_MK['boxStyle'], [
                'height' => $mk_height . 'px'
            ]);

            // 模块数据
            $mk = array_merge(self::DEFAULT_MK, [
                'boxStyle' => $mk_boxStyle,
                'groups' => [$group],
                'mk_type' => self::MK_TYPE_VIDEO
            ]);

            array_push($result, [
                'index' => $index,
                'mk' => $mk,
            ]);
        }

        return $result;
    }


    // 通过video字段的数据追加视频数据
    private static function buildVideoMkByVideos ($template, $videos) {

        return [];
    }


    // 入口
    public static function handle ($detail) {

        $template = \json_decode($detail['template'], true);
        $hotspots = \json_decode($detail['hotspot'], true);
        $gifs = \json_decode($detail['gif'], true);
        $videos = \json_decode($detail['video'], true);

        $hotspots = is_array($hotspots) ? $hotspots : [];
        $gifs = is_array($gifs) ? $gifs : [];
        $videos = is_array($videos) ? $videos : [];


        /**
         * 给现有数据补齐
         * 原有数据中, 有些项没有category字段和mk_type字段, 给它补齐
         * 原有数据的groups的每一项里只有 boxStyle、picStyle、textStyle、type
         * 动图、视频、热区作为group的一部分应该有相同的格式数据
         */
        foreach ($template as &$item) {
            
            // category
            if (!isset($item['category'])) {
                $item['category'] = 0;
            }

            // 模块类型, 老的数据里, 模块统一都是图文模块, 它的动图、视频存在两一个字段里
            if (!isset($item['mk_type'])) {
                $item['mk_type'] = self::MK_TYPE_NORMAL;
            }
            
            $groups = &$item['groups'];
            for ($i = 0, $len = count($groups); $i < $len; $i++) {

                $groups[$i] = array_merge(self::DEFAULT_GROUP, $groups[$i]);
            }
        }

        $mks = [];

        // 生成动图模块数据
        $mks = array_merge($mks, self::buildGifMk($template, $gifs));

        // 通过动图字段生成视频模块数据
        $mks = array_merge($mks, self::buildVideoMkByGifs($template, $gifs));

        // 通过视频字段生成视频模块数据
        $mks = array_merge($mks, self::buildVideoMkByVideos($template, $videos));

        // 从小到达排序
        array_multisort(array_column($mks, 'index'), SORT_ASC, $mks);


        // 追加(用foreach会有莫名的bug?)
        for ($i = 0; $i < count($mks); $i++) {
            array_splice($template, $mks[$i]['index'], 0, [$mks[$i]['mk']]);
        }


        // 追加热区数据
        $template = self::appendHotspot($template, $hotspots);

        return $template;
    }
}