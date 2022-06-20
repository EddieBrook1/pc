<?php
namespace app\extend\tb;

use app\extend\DoCurl;
use app\extend\RecordError;
use app\extend\tb\TbUtil;
use app\model\SyncMainJobModel;
use app\extend\Setting;
use app\extend\Util;
use app\extend\tb\TbWangpuToWenBen;

// 同步淘宝数据
class TbCommitItemTemplate {

    // 链接
    private const CLIENT_TYPE_PC = 0;
    private const CLIENT_TYPE_PHONE = 1;
    private const CLIENT_TYPE_BOTH = 2;

    // 已被使用的模块id
    private $groupid_been_used = [];

    // 已被使用的组件id
    private $componentid_been_used = [];


    // 生成一个当前环境唯一的模块id
    private function buildGroupId () {

        // 如果生成的id存在则继续生成
        do {

            $id = 'group' . time() . mt_rand(100, 999);
        } while (in_array($id, $this->groupid_been_used));

        array_push($this->groupid_been_used, $id);
        return $id;
    }


    // 生成一个当前环境唯一的组件id
    private function buildComponentId () {

        // 如果生成的id存在则继续生成
        do {

            $id = 'component' . 99 . mt_rand(1000, 9999) . mt_rand(1000, 9999) . mt_rand(100, 999);
        } while (in_array($id, $this->componentid_been_used));

        array_push($this->componentid_been_used, $id);
        return $id;
    }


    /** 淘宝商品链接转换, 电脑端视频链和手机端视频链接互转
     * 视频链接的话, 要求传入电脑端的视频链接
     * 
     * @param [String] $origin_link
     * @param boolean $client_type 平台
     * @return String
     */
    private function convertLink ($origin_link, $client_type) {

        preg_match('/\.mp4$/', $origin_link, $p);
        if (empty($p)) {
            // 普通商品链接

            $pc = 'https://item.taobao.com/item.htm';
            $wireless = '//h5.m.taobao.com/awp/core/detail.htm';
    
            $exploded = explode('?', $origin_link);
            $query_arr = explode('&', array_pop($exploded));
            $id = null;
            foreach ($query_arr as $item) {
                preg_match('/id=(\d+)/', $item, $p);
                if (!empty($p)) {
                    $id = $p[1];
                    break;
                }
            }
    
            // pc平台
            if ($client_type === self::CLIENT_TYPE_PC) return "$pc?id=$id";
            // 手机端
            if ($client_type === self::CLIENT_TYPE_PHONE) return "$wireless?id=$id";
        } else {
            // 视频链接

            $wireless = 'https://h5.m.taobao.com/ecrm/jump-to-app.html?target_url=';

            // pc平台
            if ($client_type === self::CLIENT_TYPE_PC) return $origin_link;
            // 手机端
            if ($client_type === self::CLIENT_TYPE_PHONE) return $wireless . urlencode($origin_link);
        }
    }


    /** 给商品详情添加一个尾图
     * 这张图片在旺铺编辑器里会显示空白, 在商品详情里正常显示
     *
     * @return Array 形如:
        [
            'detailParamItem' => $detailParamItem,
            'templateContentItem' => $templateContentItem,
        ]
     * 
     */
    private function buildSuffixPic () {

        $setting = new Setting;

        // 获取图片信息
        $pic = $setting->getAdPic2();
        
        // 唤起旺旺的链接
        $emit_wangwang_link = $setting->getEmitWangwangLink();

        // 确保图片是750宽
        $width = 750;
        $height = intval($width / $pic['width'] * $pic['height']);

        // 尾图模块的id
        $group_id = $this->buildGroupId();

        // 尾图的图片数据
        $detailParamItem = [

            // 固定
            'reuseImg' => false,
            "splitHeight" => 960,
            "imageIds" => [-1],

            // 该图片是属于哪个模块的
            "groupId" => $group_id,
    
            // 图片地址
            "imageUrls" => [$pic['url']],
    
            // 图片宽高
            "width" => $pic['width'],
            "height" => $pic['height'],
    
            // 图片字节大小, 使用5位随机数
            "size" => mt_rand(10000, 99999),
        ];


        // 尾图的模版数据
        $templateContentItem = [
            // 固定
            "type" => "group",
            "bizCode" => 0,
            "hide" => false,
            "propertyPanelVisible" => true,
            "level" => 1,
            "position" => "middle",
            "groupName" => "模块",

            // 模块盒子样式
            "boxStyle" => [
                "background-color" => "#ffffff",
                "width" => $width,
                "height" => $height,
            ],

            // 可能还有动图模块, 视频模块等等
            "bizName" => "图文模块",

            // 跟随buildDetailParam里面的字段
            "groupId" => $group_id,
            "id" => $group_id,

            // 组件
            "components" => [],
        ];


        // 尾图添加一个热区
        array_push($templateContentItem['components'], [

            'level' => 2,
            'selected' => false,
            'type' => 'component',

            'boxStyle' => [
                'background-color' => 'transparent',
                'rotate'    => 0,
                'z-index'   => 999,
                'width'     => $width,
                'height'    => $height,
                'top'       => 0,
                'left'      => 0,
            ],
            'componentId' => $this->buildComponentId(),
            'componentType' => 'hyperlink',
            'groupId' => $group_id,
            'params' => [ 'link' => $emit_wangwang_link ],
        ]);


        return [
            'detailParamItem' => $detailParamItem,
            'templateContentItem' => $templateContentItem,
        ];
    }


    /** 生成热区, 如果热区过大则需要切割
     *
     * @param [Array] $editor_data  编辑器数据
     * @param [Array] $pics         图片数据, 按图片数据来生成模块
     * @return Array 热区数据, 键名是表示第几个模块, 键值是那个模块的热区
     */
    private function buildHotspots ($editor_data, $resource_object, $client_type) {

        // 计算每个模块的上下左右距离, right = left + width; bottom = top + height
        // left 视作 0
        $pics_pos = [];
        foreach ($resource_object as $item) {
            
            $item_width = floatval($item['width']);
            $item_height = floatval($item['height']);

            $pics_pos_length = count($pics_pos);
            if ($pics_pos_length == 0) {

                $top = 0;
            } else {

                $last_item = $pics_pos[$pics_pos_length - 1];
                $top = $last_item['bottom'];
            }

            array_push($pics_pos, [
                'top'     => $top,
                'right'   => $item_width,
                'bottom'  => $top + $item_height,
                'left'    => 0,
                
                'width'   => $item_width,
                'height'  => $item_height,
            ]);
        }


        // 计算编辑器里每个热区的上下左右距离, right = left + width; bottom = top + height
        $hotspot_styles = [];
        $top_base = 0;
        foreach ($editor_data as $mk_index => $mk) {

            foreach ($mk['groups'] as $component) {

                // 不是热区跳过
                if ($component['type'] !== 'hotspot') continue;

                $boxStyle_now = $component['boxStyle'];
                $hotspot_now = [
                    'width'     => floatval($boxStyle_now['width']),
                    'height'    => floatval($boxStyle_now['height']),

                    'left'      => floatval($boxStyle_now['left']),
                    'top'       => floatval($boxStyle_now['top']) + $top_base,
                ];
                $hotspot_now['right'] = $hotspot_now['left'] + $hotspot_now['width'];
                $hotspot_now['bottom'] = $hotspot_now['top'] + $hotspot_now['height'];
                $hotspot_now['link'] = $component['hotspotStyle']['link'];

                array_push($hotspot_styles, $hotspot_now);
            }

            $top_base += floatval($mk['boxStyle']['height']);
        }


        // 碰撞检测, 此方法更多解释见README.md文档
        $hotspot_data = [];
        foreach ($pics_pos as $mk_index => $b) {

            $b_top = $b['top'];
            $b_right = $b['right'];
            $b_bottom = $b['bottom'];
            $b_left = $b['left'];

            $b_width = $b['width'];
            $b_height = $b['height'];

            foreach ($hotspot_styles as $a) {

                $a_top = $a['top'];
                $a_right = $a['right'];
                $a_bottom = $a['bottom'];
                $a_left = $a['left'];

                $a_width = $a['width'];
                $a_height = $a['height'];
                
                // 碰撞检测
                $is_crash = Util::checkCrash(
                    $a_left, $a_top, $a_width, $a_height,
                    $b_left, $b_top, $b_width, $b_height
                );

                // 没碰上跳过
                if (!$is_crash) continue;

                // 碰撞上了计算重叠部分面积
                $width_now = null;
                $height_now = null;
                $left_now = null;
                $top_now = null;

                // 计算高和top
                if ($a_top < $b_top) {

                    if ($a_bottom <= $b_bottom) {

                        $height_now = $a_bottom - $b_top;
                    } else {

                        $height_now = $b_height;
                    }

                    $top_now = 0;
                } else {

                    if ($a_bottom <= $b_bottom) {

                        $height_now = $a_height;
                    } else {

                        $height_now = $b_bottom - $a_top;
                    }

                    $top_now = $a_top - $b_top;
                }


                // 计算宽和left值
                if ($a_left < $b_left) {

                    if ($a_right <= $b_right) {

                        $width_now = $a_right - $b_left;
                    } else {

                        $width_now = $b_width;
                    }

                    $left_now = 0;
                } else {

                    if ($a_right <= $b_right) {

                        $width_now = $a_width;
                    } else {

                        $width_now = $b_right - $a_left;
                    }

                    $left_now = $a_left - $b_left;
                }


                if (!isset($hotspot_data[$mk_index])) $hotspot_data[$mk_index] = [];
                array_push($hotspot_data[$mk_index], [
                    'width' => $width_now,
                    'height' => $height_now,
                    'left' => $left_now,
                    'top' => $top_now,
                    'link' => $this->convertLink($a['link'], $client_type),
                ]);
            }
        }
        
        
        return $hotspot_data;
    }


    /** 渲染广告图
     * 在空的模版数据上渲染出广告图, ad即advertisement
     * 之前已经生成了一个空白的模版数据,
     * 接下来让每个模块都显示同一张广告图, 在根据每个模块的位置进行偏移,
     * 使最后同步出来的效果, 编辑器里是一张完整的图片
     */
    private function renderAdOnEmptyTemplete ($templateContent) {

        // 获取图片信息
        $pic = (new Setting)->getAdPic1();
        // 确保图片是750宽
        $width = 750;
        $height = intval($width / $pic['width'] * $pic['height']);

        $groups = &$templateContent['groups'];

        // 判断所有模块总高度是否足够广告图的高度
        $all_boxStyle   = array_column($groups, 'boxStyle');        // 取出所有boxStyle
        $all_height     = array_column($all_boxStyle, 'height');    // 取出所有height
        $total_height   = array_sum($all_height);                   // 计算总高度

        
        // 和广告图相比, 还查多少; 负数和0都无需更改, 如果为负数, 扩大最后一个模块的高度
        $diff = $total_height - $height;
        if ($diff < 0) {

            $last_mk = $groups[count($groups) - 1];
            $last_mk_height = $last_mk['boxStyle']['height'] + abs($diff);

            $groups[count($groups) - 1]['boxStyle']['height'] = $last_mk_height;
        }

        // 给每个模块添加同一张广告ad, 通过位移实现显示同一张图片的效果
        $offset = 0;
        foreach ($groups as &$mk) {

            // 只操作图文模块
            if ($mk['bizName'] !== '图文模块') continue;

            array_unshift($mk['components'], [
                // 固定
                "type" => "component",
                "sellerEditable" => true,
                "level" => 2,
                "clipType" => "rect",
                "componentType" => "pic",
                "componentName" => "图片组件",
                "selected" => false,

                // 自己生成
                "componentId" => $this->buildComponentId(),
            
                // 跟随buildDetailParam里面的字段
                "groupId" => $mk['groupId'],

                // 图片样式
                "imgStyle" => [
                    "top" => 0,
                    "left" => 0,
                    "width" => $width,
                    "height" => $height,
                ],

                // 盒子样式
                "boxStyle" => [
                    // 固定
                    "rotate" => "0",
                    "z-index" => "0",

                    "top" => $offset,
                    "left" => 0,
                    "width" => $width,
                    "height" => $height,
                    "background-image" => $pic['url'],
                ],
            ]);

            $offset -= $mk['boxStyle']['height'];
        }

        return $templateContent;
    }


    /** 生成图文模块
     *
     * @param [Array] $detailParam_item 图片数据项
     * @param [Number] $mk_index 千绘模版下标
     * @param [Array] $hotspot_data 热区数据
     * @param [Number] $client_type 同步端
     * @return Array
     */
    private function buildPicMk ($detailParam_item, $mk_index, $hotspot_data, $client_type) {

        // 当前模块
        $mk = [
            // 固定
            "type" => "group",
            "bizCode" => 0,
            "hide" => false,
            "propertyPanelVisible" => true,
            "level" => 1,
            "position" => "middle",
            "groupName" => "模块",

            // 模块盒子样式
            "boxStyle" => [
                "background-color" => "#ffffff",
                "width" => $detailParam_item['width'],
                "height" => $detailParam_item['height'],
            ],

            // 可能还有动图模块, 视频模块等等
            "bizName" => "图文模块",

            // 跟随buildDetailParam里面的字段
            "groupId" => $detailParam_item['groupId'],
            "id" => $detailParam_item['groupId'],

            // 组件, 只放一个图片组件
            "components" => [],
        ];

        // 不插入图片组件, 只放广告图
        $components = [];

        // 插入热区
        $hotspots = isset($hotspot_data[$mk_index]) ? $hotspot_data[$mk_index] : [];
        foreach ($hotspots as $hotspot_item) {

            array_push($components, [
                'level' => 2,
                'selected' => false,
                'type' => 'component',

                'boxStyle' => [
                    'background-color' => 'transparent',
                    'rotate'    => 0,
                    'z-index'   => 999,
                    'width'     => ceil($hotspot_item['width']),
                    'height'    => ceil($hotspot_item['height']),
                    'top'       => ceil($hotspot_item['top']),
                    'left'      => ceil($hotspot_item['left']),
                ],
                'componentId' => $this->buildComponentId(),
                'componentType' => 'hyperlink',
                'groupId' => $detailParam_item['groupId'],
                'params' => [ 'link' => $hotspot_item['link'] ],
            ]);
        }
        $mk['components'] = $components;

        return $mk;
    }


    /** 生成视频模块
     *
     * @param [Array] $editor_data 千绘模版数据
     * @param [Array] $resource_item 资源项
     * @return Array
     */
    private function buildVideoMk ($editor_data, $resource_item) {

        // 原图模块的下标
        $mk_index = $resource_item['mk_index'];
        $videoStyle = $editor_data[$mk_index]['groups'][0]['videoStyle'];

        $width = 0;
        $height = 0;

        $id = $videoStyle['id'];
        $name = $videoStyle['name'];
        $length = $videoStyle['length'];
        $videoWidth = $videoStyle['videoWidth'];
        $videoHeight = $videoStyle['videoHeight'];
        $playUrl = $videoStyle['playUrl'];
        $snapshot = $videoStyle['snapshot'];
        $video_type = $videoStyle['type'];

        // 限高
        if ($video_type === 'height_fixed') {
            $width = 620;
            $height = 420;
        }

        // 不限高
        if ($video_type === 'height_auto') {
            $width = 620;
            $height = intval($width / intval($videoWidth) * intval($videoHeight));
        }
        
        $mk = [
            // 固定
            'bizCode' => 1,
            'bizName' => "视频",
            'groupName' => "视频模块",
            'hide' => false,
            'level' => 1,
            'position' => "middle",
            'propertyPanelVisible' => true,
            'scenario' => "wde",
            'type' => "video",

            // 动态
            'boxStyle' => [
                'width' => $width,
                'height' => $height,
            ],
            'components' => [[
                // 固定
                'componentId' => '',
                'componentName' => '视频组件',
                'componentType' => "video",
                'sellerEditable' => true,
                'type' => 'component',

                'boxStyle' => [
                    'top' => 0,
                    'left' => 0,
                    'width' => $width,
                    'height' => $height,
                ],
                'groupId' => '',
                'params' => [
                    // 固定
                    'state' => 6,
                    'stateDesc' => "通过审核",
                    'videoDesc' => '',
                    'videoType' => 1,

                    // 动态
                    'coverUrl' =>  $snapshot,
                    'duration' => $length,
                    'title' => $name,
                    'videoId' => $id,
                    'videoName' => $name,
                    'videoResourceUrl' => $playUrl,
                    'videoUrl' => $playUrl,
                ],                
            ]],
            'groupId' => $this->buildGroupId(),
        ];

        return $mk;
    }


    /** 生成详情数据
     * 依赖 buildPicMk、buildVideoMk
     * 
     * @param [Array] $editor_data 千绘模版数据
     * @param [Array] $resource_object 资源数据
     * @param [Array] $hotspot_data 热区数据
     * @param [Array] $client_type 同步端
     * @return Array
     */
    private function commitDataBuilder ($editor_data, $resource_object, $hotspot_data, $client_type) {

        $detailParam = [
            'params' => [],
        ];
        $templateContent = [
            'groups' => [],
            'sellergroups' => [],
        ];
        foreach ($resource_object as $mk_index => $resource_item) {

            // 生成全局唯一id
            $groupId = $this->buildGroupId();

            // 模块类型
            $resource_type = $resource_item['type'];

            // 图片模块时生成 detailParam 字段需要的值
            if ($resource_type === 'normal' || $resource_type === 'gif') {
                $detailParam_item = [
                    // 固定
                    'reuseImg' => false,
                    "splitHeight" => 960,
                    "imageIds" => [-1],
        
                    // 该图片是属于哪个模块的, id要唯一
                    "groupId" => $groupId,
            
                    // 图片地址
                    "imageUrls" => [ $resource_item['url'] ],
            
                    // 图片宽高
                    "width" => intval($resource_item['width']),
                    "height" => intval($resource_item['height']),
            
                    // 图片字节大小, 使用5位随机数
                    "size" => mt_rand(10000, 99999),
                ];

                // 生成模块数据
                $templateContent_item = $this->buildPicMk($detailParam_item, $mk_index, $hotspot_data, $client_type);

                array_push($detailParam['params'], $detailParam_item);
                array_push($templateContent['groups'], $templateContent_item);
            }

            // 视频模块, 只有无线端才生成视频模块
            if ($resource_type == 'video' && $client_type == self::CLIENT_TYPE_PHONE) {
                $templateContent_item = $this->buildVideoMk($editor_data, $resource_item);
                array_push($templateContent['groups'], $templateContent_item);
            }
        }

        return [
            'detailParam' => $detailParam,
            'templateContent' => $templateContent,
        ];
    }


    /** 判断淘宝返回数据是否正常
     * 可能的错误代码
     *      - SYSTEM_ERROR  系统错误
     *      - TB_UN_LOGIN   未登录
     *      - TB_WIRELESS_LINK_ERR 热区链接非淘系无线端链接
     *
     * @param [Array] $raw
     * @return boolean
     */
    private function isApiOk ($raw) {

        // 尝试转成数组
        try {
            
            $input = json_decode($raw, true);

        } catch (\Throwable $th) { $input = null; }

        // 无法转换成数组, 退出
        if (!is_array($input)) return 'SYSTEM_ERROR';

        // 通过这三个字段判断, 缺少一个无法判断
        if (
            !array_key_exists('error', $input) ||
            !array_key_exists('code', $input) ||
            !array_key_exists('msg', $input)
        ) return 'SYSTEM_ERROR';

        $error = $input['error'];
        $code = $input['code'];
        $msg = $input['msg'];

        // 正常
        if ($code === 0 && $error === false) return true;


        // --- 异常

        // 未登录
        preg_match('/登录失效/', $msg, $p);
        if ($code === 1000 && !empty($p)) return 'TB_UN_LOGIN';

        // 热区链接非淘系无线端链接
        preg_match('/热区链接非淘系无线端链接/', $msg, $p);
        if ($code === 999 && !empty($p)) return 'TB_WIRELESS_LINK_ERR';

        // 没匹配上, 返回"系统异常"
        return 'SYSTEM_ERROR';
    }


    // 判断是否有动图
    private function isHasGif ($resource_object) {
        
        foreach ($resource_object as $item) {
            preg_match('/\.gif$/', $item['url'], $p);
            if (!empty($p)) return true;
        }
        return false;
    }


    /** 执行入口
     *
     * @param [String] $cookie 客户cookie
     * @param [Array] $editor_data 模版数据
     * @param [Array] $resource_object 资源数据
     * @param [Number] $goods_id 商品id
     * @param [Number] $client_type 同步拿一端
     * @return void
     */
    private function exec ($exec_param) {

        [
            'cookie' => $cookie,
            'editor_data' => $editor_data,
            'resource_object' => $resource_object,
            'goods_id' => $goods_id,
            'client_type' => $client_type,
        ] = $exec_param;

        // 是否提交空数据
        $submit_empty = isset($exec_param['submit_empty']) ? $exec_param['submit_empty'] : false;


        // 接口
        $_tb_token_ = TbUtil::getTbToken($cookie);
        $url = "https://xiangqing.wangpu.taobao.com/template/ajax/commit_item_template.do?_input_charset=utf-8&_tb_token_=$_tb_token_";
        

        if ($submit_empty) {
            // --- 提交空数据

            // 要上传的数据
            $post_data = [
                // 固定
                'opt' => 2,
                'templateId' => 0,
                'freeTry' => 0,
                'version' => 1,
                'checkLevel' => 'group',
                'bizSource' => '',
                
                'clientType' => $client_type,
                'templateContent' => '{"groups":[],"sellergroups":[]}',
                'itemId' => $goods_id,
            ];

        } else {
            // --- 不提交空数据

            // 生成热区数据
            $hotspot_data = $this->buildHotspots($editor_data, $resource_object, $client_type);
    
            // 生成详情数据
            $commit_data = $this->commitDataBuilder($editor_data, $resource_object, $hotspot_data, $client_type);
            $templateContent = $commit_data['templateContent'];
            $detailParam = $commit_data['detailParam'];
    
            // 尾图广告, PC端加, 手机端不加
            if ($client_type === self::CLIENT_TYPE_PC) {
                $suffix_data = $this->buildSuffixPic();
                array_push($templateContent['groups'], $suffix_data['templateContentItem']);
                array_push($detailParam['params'], $suffix_data['detailParamItem']);
            }
    
            // 给留空的模块数据插入广告图
            $templateContent = $this->renderAdOnEmptyTemplete($templateContent);
    
            // 要上传的数据
            $post_data = [
                // 固定
                'opt' => 2,
                'templateId' => 0,
                'freeTry' => 0,
                'version' => 1,
                'checkLevel' => 'group',
                'bizSource' => '',
                
                'clientType' => $client_type,
                'templateContent' => json_encode($templateContent, 320),
                'detailParam' => json_encode($detailParam, 320),
                'itemId' => $goods_id,
            ];
        }

        // 要转成 urlencode 格式
        $post_str_arr = [];
        foreach ($post_data as $key => $val) {

            array_push($post_str_arr, $key . '=' . urlencode($val));
        }
        $post_str = implode('&', $post_str_arr);

        // 发送请求
        $doCurl = new DoCurl([ 'cookie' => $cookie ]);
        $response = $doCurl->post($url, $post_str);

        $isOk = $response['isOk'];
        $raw = $response['raw'];


        // 请求异常, 提前返回
        if (!$isOk) {

            RecordError::handle(
                __CLASS__,
                "链接($url)",
                '淘宝接口http请求错误'
            );
            return Util::formatResult([], false, 'SYSTEM_ERR');
        }

        
        // 淘宝接口返回异常, 提前返回
        $err_name = $this->isApiOk($raw);
        if ($err_name !== true) {

            RecordError::handle(
                __CLASS__,
                '数据: "' . $raw .'"',
                '淘宝返回数据异常'
            );
            return Util::formatResult([], false, $err_name);
        }

        // 返回
        return Util::formatResult([], true);
    }


    // 入口
    public function handle ($cookie, $main_job_id) {

        // 主任务
        $mainJob = SyncMainJobModel::find($main_job_id);
        
        // 编辑器数据
        $editor_data = json_decode($mainJob->editor_data, true);
        // 图片数据
        $resource_object = json_decode($mainJob->resource_object, true);
        // 商品id
        $goods_id = $mainJob->goods_id;
        // 同步哪一端
        $client_type = $mainJob->client_type;
        
        $exec_param = [
            'cookie' => $cookie,
            'editor_data' => $editor_data,
            'resource_object' => $resource_object,
            'goods_id' => $goods_id,
            'client_type' => null
        ];


        if ($this->isHasGif($resource_object)) {
            // 有动图, 需给前端打开iframe的方式同步

            $exec_param['client_type'] = self::CLIENT_TYPE_PHONE;
            $exec_param['submit_empty'] = true;

            // 手机端同步空数据
            $result = $this->exec($exec_param);
            if ($result['error']) {
                return Util::formatResult([], false, 'SYNC_ERR', $result['msg']);
            }

            // 生成旺铺数据格式
            $hotspot_data = $this->buildHotspots($editor_data, $resource_object, self::CLIENT_TYPE_PC);
            $commit_data = $this->commitDataBuilder($editor_data, $resource_object, $hotspot_data, $client_type);
            $templateContent = $commit_data['templateContent'];
            $detailParam = $commit_data['detailParam'];
            // 尾图广告
            $suffix_data = $this->buildSuffixPic();
            array_push($templateContent['groups'], $suffix_data['templateContentItem']);
            array_push($detailParam['params'], $suffix_data['detailParamItem']);

            // 转成文本编辑格式的数据
            $template_content_text = (new TbWangpuToWenBen)->handle($detailParam, $templateContent);

            return Util::formatResult([
                'template_content_text' => $template_content_text,
            ], true, 'SYNC_PREPARE');

        } else {
            // 没动图, 正常同步

            if ($client_type != self::CLIENT_TYPE_BOTH) {
                // 只有一端
    
                $exec_param['client_type'] = $client_type;
                $result = $this->exec($exec_param);
                return $result['error']
                    ? Util::formatResult([], false, 'SYNC_ERR', $result['msg'])
                    : Util::formatResult([], true);
    
            } else {
                // 两端
    
                // pc端
                $exec_param['client_type'] = self::CLIENT_TYPE_PC;
                $pc_result = $this->exec($exec_param);
                
                // 手机端
                $exec_param['client_type'] = self::CLIENT_TYPE_PHONE;
                $phone_result = $this->exec($exec_param);
                
                if (!$pc_result['error'] && !$phone_result['error']) {
                    // 两端都成功
                    return Util::formatResult([], true);
                } else {
                    // 其中一端失败
    
                    if ($pc_result['error']) {
                        $msg = '电脑端失败, ' . $pc_result['msg'];
                    }
                    
                    if ($phone_result['error']) {
                        $msg = '手机端失败, ' . $pc_result['msg'];
                    }
    
                    if ($pc_result['error'] && $phone_result['error']) {
                        $msg = '两端失败, ' . $pc_result['msg'] . ', ' . $phone_result['msg'];
                    }
    
                    return Util::formatResult([], false, 'SYNC_ERR', $msg);
                }
            }

        }
    }
}