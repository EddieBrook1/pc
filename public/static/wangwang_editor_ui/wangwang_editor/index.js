// var editor = Editor('#editor')

// editor.reload(data)                         // 重载编辑器
// editor.append(index, model_data)            // 追加模块在某个模块后面
// editor.delete(index)                        // 删除某个模块
// editor.destroy()                            // 销毁整个编辑器内容, 即清空编辑器
// editor.getData(index)                       // 获取某个模块的对应的数据
// editor.getAllData()                         // 获取整个编辑器的对应的数据
// editor.copyModule(copy_index, dist_index)   // 复制某个模块到某个模块后面
// editor.cutModule(cut_index, dist_index)     // 剪切某个模块到某个模块后面
// editor.copyItem(module_index, item_index, dist_module_index)    // 复制模块里某个组件到某个模块里
// editor.cutItem(module_index, item_index, dist_module_index)     // 剪切模块里某个组件到某个模块里

// 内部方法
// editor.renderAll()      // 渲染整个编辑器
// editor.render()         // 渲染某个模块


/**
 * cur即 current 表示当前的意思
 */
 function Editor (selector, source) {

    /**
     * 拖拽方法
     * @param {Object} $el jQDOM
     * @param {Object} input_config 配置, 详细见代码开头
     * @returns Object  obj.unbind  解绑拖拽事件
     */
    function Drag ($el, input_config) {

        // 入参
        var config = {

            // 可运动方向, both两个方向都可以, x横向, y竖向
            axis: 'both',
            // 是否禁用默认事件
            prevent_default: true,
            // 移动前调用
            onStart: function () {},
            // 移动时调用
            onMove: function () {},
            // 移动结束时调用
            onEnd: function () {},
        }
        config = Object.assign({}, config, input_config)


        // mousedown的clientX和clientY
        var st_x = 0
        var st_y = 0
        // mousedown的$el.offset()值
        var st_offset_x = 0
        var st_offset_y = 0
        var parent_st_left = 0
        var parent_st_top = 0
        // 是否禁止移动
        var is_disable = false


        // 三个事件
        var mousedownFn = function (e) {
            
            // 记录本次事件是否为真实事件
            var is_real_event = (e.clientX == null || e.clientY == null)
                ? false
                : true

            st_x = e.clientX
            st_y = e.clientY

            var offset = $el.offset()
            st_offset_x = offset.left
            st_offset_y = offset.top
            $(document).on('mousemove', mousemoveFn)
            $(document).on('mouseup', mouseupFn)

            var parent_pos = $el.offsetParent().position()
            parent_st_left = parent_pos.left
            parent_st_top = parent_pos.top

            // 执行回调
            config.onStart($el, is_real_event)

            // 阻止默认事件, 防止元素被选中
            if (config.prevent_default) {
                e.preventDefault()
            }
        }
        var mousemoveFn = function (e) {

            // 是否禁止移动
            if (is_disable) return

            // 定位父级可能位置变换,　若不想跟随父级需减掉父级移动的差值
            var cur_parent_pos = $el.offsetParent().position()
            var parent_diff_left = cur_parent_pos.left - parent_st_left
            var parent_diff_top = cur_parent_pos.top - parent_st_top

            var diff_x = e.clientX - st_x + parent_diff_left
            var diff_y = e.clientY - st_y + parent_diff_top

            if (config.axis == 'x') {

                $el.offset({
                    left: st_offset_x + diff_x,
                })
            } else if (config.axis == 'y') {

                $el.offset({
                    top: st_offset_y + diff_y,
                })
            } else {

                $el.offset({
                    left: st_offset_x + diff_x,
                    top: st_offset_y + diff_y,
                })
            }

            // 执行回调
            config.onMove($el)
        }
        var mouseupFn = function (e) {
            
            $(document).off('mousemove', mousemoveFn)
            $(document).off('mouseup', mouseupFn)

            // 执行回调
            config.onEnd($el)
        }
        $el.on('mousedown', mousedownFn)


        // 解绑
        var unbind = function () {

            $el.off('mousedown', mousedownFn)
            $(document).off('mousemove', mousemoveFn)
            $(document).off('mouseup', mouseupFn)
        }


        // 暂时禁止移动
        var disable = function () {
            is_disable = true
        }


        // 允许移动
        var enable = function () {

            is_disable = false
        }

        return {
            unbind: unbind,
            disable: disable,
            enable: enable,
        }
    }


    /**
     * 工具方法
     * @method isTarget             当前点击的DOM与目标DOM进行匹配, 然后执行对应回调
     * @method onDocumentClick      全局点击事件, 点击时的DOM与目标DOM进行匹配, 然后执行对应回调
     * @method isComponent          当前点击的DOM是否为组件
     * @method isMk                 当前点击的DOM是否为模块
     * @method isBoxLine            当前点击的DOM是否为边框线
     * @method isTextComponent      当前点击的DOM是否为文字组件
     */
    var util = (function () {

        /**
         * 判断 cur_el或cur_el的上级是否和target相同, 相同执行succFn回调, 不同执行failFn回调
         * @param {Object} param { cur_el: 当前点中的e.target, target: 被用来比对的DOM, succFn: 匹配成功回调, failFn: 匹配失败回调 }
         */
        var isTarget = function (param) {

            var cur_el = param.cur_el
            var target = param.target
            var succFn = param.succFn
            var failFn = param.failFn
    
            // target必须为数组, 如果不是数组转成数组
            target = !(target instanceof Array)
                ? target = [target]
                : target
    
            for (var i = 0, len = target.length; i < len; i++) {
    
                if (cur_el == target[i]) {
    
                    ;(succFn instanceof Function) && succFn()
                    return
                }
            }
    
            var $cur_el = $(cur_el)
            var $target_parents = $cur_el.parents()
            
            for (var i = 0, len = $target_parents.length; i < len; i++) {
                for (var j = 0, jlen = target.length; j < jlen; j++) {
    
                    if ($target_parents.eq(i)[0] == target[j]) {
    
                        ;(succFn instanceof Function) && succFn()
                        return
                    }
    
                }
            }
    
            ;(failFn instanceof Function) && failFn()
        }


        /**
         * 立即执行函数, onDocumentClick最终是一个函数
         * 
         * onDocumentClick($target, succFn, failFn)
         * 全局点击事件, 点击其他地方, 判断是否为目标元素, 并调用对应方法
         * @param {JQDOM} $target 被用来比对的DOM
         * @param {Function} succFn 比对成功的回调
         * @param {Function} failFn 比对失败的回调
         */
        var onDocumentClick = (function () {

            // 要判断元素集
            var fns = []

            // 点击其他地方隐藏
            $(document).on('click', function (e) {

                for (var i = 0, len = fns.length; i < len; i++) {

                    try {
                        
                        fns[i](e)
                    } catch (error) {

                        console.warn(error)
                    }
                }
            })


            return function ($target, succFn, failFn) {

                succFn = (succFn instanceof Function) ? succFn : function () {}
                failFn = (failFn instanceof Function) ? failFn : function () {}
                fns.push(function (e) {

                    isTarget({
                
                        cur_el: e.target,
                        target: $target[0],
                        succFn: succFn,
                        failFn: failFn,
                    })
                })
            }
        })()


        // 判断$target或其上级是否为组件, 是返回该组件, 否则返回false
        var isComponent = function ($target) {

            var type = $target.attr('el-type')
            if (type == EL_TYPE_COMPONENT_PIC || type == EL_TYPE_COMPONENT_TEXT) return $target

            var $el = $target.parents('[el-type^="component"]')
            if ($el.length > 0) return $el.eq(0)

            return false
        }


        // 判断$target或其上级是否为模块, 是返回该模块, 否则返回false
        var isMk = function ($target) {

            if ($target.attr('el-type') == 'mk') return $target

            var $el = $target.parents('[el-type="mk"]')
            if ($el.length > 0) return $el.eq(0)

            return false
        }


        // 判断$target或其上级是否为边框线, 是返回该边框线, 否则返回false
        var isBoxLine = function ($target) {

            var type = $target.attr('el-type')
            if (type == EL_TYPE_BOX_LINE) return $target

            var $el = $target.parents('[el-type="' + EL_TYPE_BOX_LINE + '"]')
            if ($el.length > 0) return $el.eq(0)

            return false
        }

        
        // 判断$target或其上级是否为文字组件, 是返回该文字组件, 否则返回false
        var isTextComponent = function ($target) {

            if ($target.attr('el-type') == EL_TYPE_COMPONENT_TEXT) return $target

            var $el = $target.parents('[el-type="' + EL_TYPE_COMPONENT_TEXT + '"]')
            if ($el.length > 0) return $el.eq(0)

            return false
        }

        
        // 判断$target或其上级是否为图片组件, 是返回该图片组件, 否则返回false
        var isPicComponent = function ($target) {

            if ($target.attr('el-type') == EL_TYPE_COMPONENT_PIC) return $target

            var $el = $target.parents('[el-type="' + EL_TYPE_COMPONENT_PIC + '"]')
            if ($el.length > 0) return $el.eq(0)

            return false
        }


        // 判断$target或其上级是否为热区, 是返回该图片组件, 否则返回false
        var isHotspot = function ($target) {

            if ($target.attr('el-type') == EL_TYPE_HOTSPOT) return $target

            var $el = $target.parents('[el-type="' + EL_TYPE_HOTSPOT + '"]')
            if ($el.length > 0) return $el.eq(0)

            return false
        }


        // 节流
        var throttle = function(func, delay) {   

            var prev = Date.now()
            return function() {

                var context = this
                var args = arguments
                var now = Date.now()
                if (now - prev >= delay) {

                    func.apply(context, args)
                    prev = Date.now()
                }            
            }        
        }


        /**
         * 观察者模式, 里面有两个方法 on和emit, on侦听事件, emit发布事件
         */
        var observer = (function () {

            var cache = {}

            // 订阅
            var on = function (event_name, callback) {

                if (!(callback instanceof Function)) return
                if (!(cache[event_name] instanceof Array)) {

                    cache[event_name] = []
                }
                cache[event_name].push(callback)
            }


            // 发布
            var emit = function () {

                // 类数组转成真数组
                var param = []
                for (var i = 0, len = arguments.length; i < len; i++) {
                    param.push(arguments[i])
                }
            
                // 第一个视作方法名, 其他的视为参数
                var event_name = param.shift()
            
                // 如果存在事件名对应的处理函数则执行
                var fns = cache[event_name]
                if (fns instanceof Array) {
            
                    // 执行所有侦听方法
                    for (var i = 0, len = fns.length; i < len; i++) {
            
                        fns[i].apply(null, param)
                    }
                }
            }


            return {
                on: on,
                emit: emit,
            }
        })()


        // 数组相关工具方法
        var array = (function () {

            // 数组去重
            var unique = function (data) {

                var res = []
                var item = null
                for (var i = 0, len = data.length; i < len; i++) {

                    item = data[i]
                    if (res.indexOf(item) == -1) res.push(item)
                }
                return res
            }


            /**
             * 取出二维数组对象中的某一列, 如
                [{
                    name: 'lee',
                    age: 18,
                }, {
                    name: 'jack',
                    age: 19,
                }]
                调用此方法后返回 ['lee', 'jack']
             * 
             * @param {Array} data 二维数组
             * @param {String} field 要取的字段
             * @returns Array
             */
            var column = function (data, field) {

                var res = []
                var item = null
                for (var i = 0, len = data.length; i < len; i++) {

                    item = data[i]
                    if (item.toString() === '[object Object]') {
                        
                        if (item[field]) res.push(item[field])
                    }
                }

                return res
            }


            return {
                unique: unique,
                column: column,
            }
        })()


        // 延迟一步执行
        var nextTick = function (fn) {
            
            setTimeout(fn, 0)
        }


        // 尝试转换为整数, 如果转换失败则使用第二个参数返回
        var toInt = function (number, default_val) {

            return isNaN(parseInt(number))
                ? default_val
                : parseInt(number)
        }


        /**
         * 计算目标元素在指定父元素内的中心位置
         * 如果有传入left和top则按它们的值来但不能超过最大值, 如果某个缺少那就居中对齐
         * @param {Object} param
         *      @param param.parent_width   父级宽度
         *      @param param.parent_height  父级高度
         *      @param param.target_width   目标宽度
         *      @param param.target_height  目标高度
         *      @param param.left           目标left
         *      @param param.top            目标top
         * @returns {Object} {top: xxx, left: xxx}
         */
        var getCenterPos = function (param) {

            var parent_width = param.parent_width
            var parent_height = param.parent_height
            var target_width = param.target_width
            var target_height = param.target_height
            var left = param.left
            var top = param.top

            var max_left = parent_width - target_width
            var max_top = parent_height - target_height
            
            var cur_left = 0
            var cur_top = 0

            // 判断横边, 如果是auto则设置为中间, 否则按left值来,　但不能超出边界
            if (left == 'auto') {

                cur_left = parent_width / 2 - target_width / 2
            } else {

                cur_left = left >= max_left ? max_left : left
            }

            // 判断横边, 如果是auto则设置为中间, 否则按top值来,　但不能超出边界
            if (top == 'auto') {

                cur_top = parent_height / 2 - target_height / 2
            } else {

                cur_top = top >= max_top ? max_top : top
            }

            return {
                left: cur_left,
                top: cur_top,
            }
        }


        return {
            isTarget: isTarget,
            onDocumentClick: onDocumentClick,
            isComponent: isComponent,
            isMk: isMk,
            isBoxLine: isBoxLine,
            isTextComponent: isTextComponent,
            isPicComponent: isPicComponent,
            isHotspot: isHotspot,
            observer: observer,
            throttle: throttle,
            array: array,
            nextTick: nextTick,
            toInt: toInt,
            getCenterPos: getCenterPos,
        }
    })()


    // 编辑器
    var $editor = $(selector)

    // 数据里两种组件类型标识
    var COMPONENT_TYPE_PIC = 'pic'
    var COMPONENT_TYPE_TEXT = 'text'
    var COMPONENT_TYPE_HOTSPOT = 'hotspot'
    var COMPONENT_TYPE_GIF = 'gif'
    var COMPONENT_TYPE_VIDEO = 'video'

    // 元素类型
    var EL_TYPE_MK              = 'mk'                  // 模块
    var EL_TYPE_COMPONENT_PIC   = 'component-pic'       // 图片组件
    var EL_TYPE_COMPONENT_TEXT  = 'component-text'      // 文字组件
    var EL_TYPE_GIF             = 'component-gif'       // 动图
    var EL_TYPE_VIDEO           = 'component-video'     // 视频
    var EL_TYPE_HOTSPOT         = 'hotspot'             // 热区
    var EL_TYPE_BOX_LINE        = 'box-line'            // 边框线

    // 事件名称 - 编辑器高度变化
    var EV_EDITOR_HEIGHT_CHANGE = 'editorHeightChange'

    // 编辑器宽度
    var EDITOR_WIDTH = 750
    // 模块最低高度
    var MK_MIN_HEIGHT = 50

    // 模块的默认样式
    var mk_default_style = {
        'position': 'relative',
        'overflow': 'hidden',
        'width': '100%',
        'height': '400px',
        'background-color': 'white',
        'max-height': '20000px',
        'background-repeat': 'no-repeat',
        'background-size': 'auto',
    }
    // 模块组件的默认样式
    var group_default_style = {
        'position': 'absolute',
        'border': '0',
        'cursor': 'move',
        'overflow': 'hidden',
        'z-index': 0,
    }
    // 图片组件的默认样式
    var group_pic_default_style = {
        'position': 'absolute',
        'overflow': 'hidden',
    }
    // 文字组件的默认样式
    var group_text_default_style = {
        'resize': 'none',
        'writing-mode': 'horizontal-tb',
        'position': 'absolute',
        'width': '100%',
        'height': '100%',
        'overflow': 'hidden',
        'cursor': 'move',
        'z-index': 0,
        'font-weight': 'normal',
    }
    // 热区默认样式
    var group_hotspot_default_style = {
        'width': '100%',
        'height': '100%',
        'opacity': '0.6',
        'background-color': '#3089DC',
    }
    // 动图默认样式
    var group_gif_default_style = {
        'display': 'block',
        'width': '100%',
        'height': '100%',
    }
    // 视频默认样式
    var group_video_default_style = {
        'width': '100%',
        'height': '100%',
    }
    // 模块默认的数据格式
    var mkDefaultData = function () {
        
        return {
            "boxStyle": {
                "width": "750px",
                "height": "300px",
                "background-color": "rgb(255, 255, 255)",
                "background-image": "none",
                "background-repeat": "no-repeat"
            },
            "groups": [],
            "category": 0,
        }
    }
    // 图片组件默认的数据格式
    var groupPicData = function () {

        return {
            "boxStyle": {
                "width": "0px",
                "height": "0px",
                "top": "0px",
                "left": "0px",
                "z-index": "0",
                "border-radius": "0px",
                "guding": 0,
                "biaoji": "0",
                "type": "",
            },
            "textStyle": {},
            "picStyle": {
                "width": "0px",
                "height": "0px",
                "top": "0px",
                "left": "0px",
                "scaling_width": 0,
                "scaling_height": 0,
                "src": ""
            },
            "hotspotStyle": {},
            "gifStyle": {},
            "videoStyle": {},
            "type": "pic"
        }
    }
    // 文字组件默认的数据格式
    var groupTextData = function () {

        return {
            "boxStyle": {
                "width": "0px",
                "height": "0px",
                "top": "0px",
                "left": "0px",
                "z-index": "0",
                "border-radius": "0px",
                "guding": 0,
                "biaoji": 0,
                "type": "",
            },
            "textStyle": {
                "font-size": "14px",
                "font-family": "simsun",
                "color": "rgb(0, 0, 0)",
                "text-align": "left",
                "font-weight": "normal",
                "background-color": "rgba(0, 0, 0, 0)",
                "value": "10",
                "font-style": "normal",
                "letter-spacing": "0px",
                "line-height": "1.5"
            },
            "picStyle": {},
            "hotspotStyle": {},
            "gifStyle": {},
            "videoStyle": {},
            "type": "text"
        }
    }
    // 热区默认数据格式
    var groupHotspotData  = function () {
        
        return {
            "boxStyle": {
                "width": "0px",
                "height": "0px",
                "top": "0px",
                "left": "0px",
                "z-index": "0",
                "border-radius": "0px",
                "guding": 0,
                "biaoji": 0,
                "type": "",
            },
            "textStyle": {},
            "picStyle": {},
            "hotspotStyle": {
                "src": ""
            },
            "gifStyle": {},
            "videoStyle": {},
            "type": "hotspot",
        }
    }
    // 动图默认数据格式
    var groupGifData = function () {
        
        return {
            "boxStyle": {
                "width": "0px",
                "height": "0px",
                "top": "0px",
                "left": "0px",
                "z-index": "0",
                "border-radius": "0px",
                "guding": 0,
                "biaoji": 0,
                "type": "",
            },
            "textStyle": {},
            "picStyle": {},
            "hotspotStyle": {},
            "gifStyle": {
                "src": ""
            },
            "videoStyle": {},
            "type": "gif",
        }
    }
    // 视频默认数据格式
    var groupVideoData = function () {

        return {
            "boxStyle": {
                "width": "0px",
                "height": "0px",
                "top": "0px",
                "left": "0px",
                "z-index": "0",
                "border-radius": "0px",
                "guding": 0,
                "biaoji": 0,
                "type": "",
            },
            "textStyle": {},
            "picStyle": {},
            "hotspotStyle": {},
            "gifStyle": {},
            "videoStyle": {
                "src": "",
            },
            "type": "video",
        }
    }


    // 生成必要辅助元素
    var elBuilder = (function () {

        // 内容所在实体
        var $entity = $('<div class="entity"></div>').appendTo($editor)

        // 生成一个边框线DOM
        var buildResizeLines = function (class_name) {

            return $('\
                <div class="' + class_name + '" el-type="' + EL_TYPE_BOX_LINE + '">\
                    <div el-type="line-top" class="line top"></div>\
                    <div el-type="line-right" class="line right"></div>\
                    <div el-type="line-bottom" class="line bottom"></div>\
                    <div el-type="line-left" class="line left"></div>\
                    <div el-type="dot-top" class="dot top"></div>\
                    <div el-type="dot-top-center" class="dot top-center"></div>\
                    <div el-type="dot-right" class="dot right"></div>\
                    <div el-type="dot-right-center" class="dot right-center"></div>\
                    <div el-type="dot-bottom" class="dot bottom"></div>\
                    <div el-type="dot-bottom-center" class="dot bottom-center"></div>\
                    <div el-type="dot-left" class="dot left"></div>\
                    <div el-type="dot-left-center" class="dot left-center"></div>\
                    \
                    <div el-type="dot-cover-top" class="dot-cover top"></div>\
                    <div el-type="dot-cover-top-center" class="dot-cover top-center"></div>\
                    <div el-type="dot-cover-right" class="dot-cover right"></div>\
                    <div el-type="dot-cover-right-center" class="dot-cover right-center"></div>\
                    <div el-type="dot-cover-bottom" class="dot-cover bottom"></div>\
                    <div el-type="dot-cover-bottom-center" class="dot-cover bottom-center"></div>\
                    <div el-type="dot-cover-left" class="dot-cover left"></div>\
                    <div el-type="dot-cover-left-center" class="dot-cover left-center"></div>\
                </div>\
            ')
        }

        // 组件内容调整大小的边框线, 微调,　自定义拖拽点样式
        var $mkResizeLines = buildResizeLines('mk-resize-lines')
        $mkResizeLines.find('[el-type="dot-bottom-center"]').append('<i></i>')

        // 组件内容调整大小的边框线
        var $componentResizeLines = buildResizeLines('component-resize-lines')

        // 裁剪图片时的边框线
        var $trimResizeLines = buildResizeLines('trim-resize-lines')

        // 热区边框线
        var $hotspotResizeLines = buildResizeLines('hotspot-resize-lines')

        // 辅助输入框, 文字组件需要用到, 当编辑文字时把内容复制到这个div里, 并设置成相同的style除了高, 然后用它的高更新文字组件的高
        var $auxiliary_input = $('<div wrap="hard" spellcheck="false" contenteditable="plaintext-only"></div>')
        $auxiliary_input.hide()

        // 辅助线, 6条边, 层级最高
        var $guide_line_top    = $('<div class="guide-line top"></div>').css('z-index', 1000)
        var $guide_line_right  = $('<div class="guide-line right"></div>').css('z-index', 1000)
        var $guide_line_bottom = $('<div class="guide-line bottom"></div>').css('z-index', 1000)
        var $guide_line_left   = $('<div class="guide-line left"></div>').css('z-index', 1000)


        // 统一设置DOM的层级
        $entity.css('z-index', 1)                   // 存放图片组件、文字组件的div
        $componentResizeLines.css('z-index', 999)   // 组件边框线
        $trimResizeLines.css('z-index', 998)        // 裁剪边框线
        $mkResizeLines.css('z-index', 997)          // 模块边框线
        $hotspotResizeLines.css('z-index', 996)           // 热区边框线

        // 加入文档
        $editor.append($entity)
        $editor.append($mkResizeLines)
        $editor.append($componentResizeLines)
        $editor.append($trimResizeLines)
        $editor.append($hotspotResizeLines)
        $editor.append($auxiliary_input)
        $editor.append($guide_line_top)
        $editor.append($guide_line_right)
        $editor.append($guide_line_bottom)
        $editor.append($guide_line_left)

        return {
            $entity:                $entity,
            $mkResizeLines:         $mkResizeLines,
            $componentResizeLines:  $componentResizeLines,
            $auxiliary_input:       $auxiliary_input,
            $trimResizeLines:       $trimResizeLines,
            $hotspotResizeLines:          $hotspotResizeLines,
            $guide_line_top:        $guide_line_top,
            $guide_line_right:      $guide_line_right,
            $guide_line_bottom:     $guide_line_bottom,
            $guide_line_left:       $guide_line_left,
        }
    })()
    // 编辑器实体
    var $entity                 = elBuilder.$entity
    // 模块边框线
    var $mkResizeLines          = elBuilder.$mkResizeLines
    // 组件边框线
    var $componentResizeLines   = elBuilder.$componentResizeLines
    // 辅助文字输入框
    var $auxiliary_input        = elBuilder.$auxiliary_input
    // 裁剪边框线
    var $trimResizeLines        = elBuilder.$trimResizeLines
    // 热区边框线
    var $hotspotResizeLines     = elBuilder.$hotspotResizeLines
    // 4条对齐辅助线
    var $guide_line_top         = elBuilder.$guide_line_top
    var $guide_line_right       = elBuilder.$guide_line_right
    var $guide_line_bottom      = elBuilder.$guide_line_bottom
    var $guide_line_left        = elBuilder.$guide_line_left


    /* -------------------- 边框线、辅助线 -------------------- */
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /**
     * 边框线附着, 边框线为“一线通用”, 即DOM只有一份, 但通过改变定位的方式让它能显示在其他地方
     * 边框线需要一个宿主, 边框线会显示在宿主上, 看起来像是真的边框
     * 边框线默认有8个拖拽点, 通过拖拽这些点会改变宿主的大小, 如果需要关闭某些点可以配置config.disable_dots
     * 此方法更多解释见README.md文档
     * 
     * @param {Object} config 配置
     *      $lines          边框线, 结构必须是 elBuilder里的buildResizeLines生成的
     *      onStart         移动前调用, 传入宿主
     *      onMove          移动时调用, 传入一个对象包括宿主宽高和位置
     *      disable_dots    要禁止的拖拽点, 可选值可选值, 'top', 'top-center', 'right', 'right-center', 'bottom', 'bottom-center', 'left', 'left-center'
     * 
     * @returns {Object} {show, hide}
     *      show($host, parent_pos)显示边框线, $host是要附着的元素, parent_pos是偏移量
     *      hide()隐藏边框线
     */
    var BoxLine = function (config) {

        var $lines = config.$lines
        var onMoveCallback = config.onMove
        var onStartCallback = config.onStart
        var disable_dots = config.disable_dots || []


        // 隐藏拖拽点位
        for (var i = 0, len = disable_dots.length; i < len; i++) {

            // 隐藏显示的点
            $lines.find('[el-type="dot-' + disable_dots[i] + '"]').hide()

            // 隐藏被拖拽的遮罩点
            $lines.find('[el-type="dot-cover-' + disable_dots[i] + '"]').hide()
        }


        /**
         * dot-cover是不可见的但占位置, 它比可见的dot会大一点, 目的是让它的cursor更容易更改, 
         * 同时让dot-cover可移动, 通过计数它的偏移量来控制组件缩放
         */
        var $dot_top            = $lines.find('[el-type="dot-cover-top"]')
        var $dot_top_center     = $lines.find('[el-type="dot-cover-top-center"]')
        var $dot_right          = $lines.find('[el-type="dot-cover-right"]')
        var $dot_right_center   = $lines.find('[el-type="dot-cover-right-center"]')
        var $dot_bottom         = $lines.find('[el-type="dot-cover-bottom"]')
        var $dot_bottom_center  = $lines.find('[el-type="dot-cover-bottom-center"]')
        var $dot_left           = $lines.find('[el-type="dot-cover-left"]')
        var $dot_left_center    = $lines.find('[el-type="dot-cover-left-center"]')


        // 当前宿主
        var $host_now = null
        // 当前宿主父级的定位
        var ht_parent_pos = null
        // 当前宿主的宽高比, 即 比例 = 宽 / 高,  宽 = 高 * 比例,  高 = 宽 / 比例
        var ht_aspect_ratio = 1
        // 当前宿主起始参数
        var host_width_st = 0
        var host_height_st = 0
        var host_left_st = 0
        var host_top_st = 0
        // 正在被移动的点的起始参数
        var dot_left_st = 0
        var dot_top_st = 0


        var drag_config = {

            // 移动前, 记录起始参数
            onStart: function ($el) {
                
                var dot_pos = $el.position()
                dot_left_st = dot_pos.left
                dot_top_st = dot_pos.top

                // 计算起始参数和宽高比
                var host_pos    = $host_now.position()
                host_left_st    = host_pos.left
                host_top_st     = host_pos.top
                host_width_st   = $host_now.width()
                host_height_st  = $host_now.height()
                ht_aspect_ratio = host_width_st / host_height_st

                if (onStartCallback instanceof Function) {
                    
                    onStartCallback($host_now)
                }
            },

            // 移动时
            onMove: function ($el) {


                var el_type = $el.attr('el-type')
                var pos = $el.position()
                var diff_left = pos.left - dot_left_st
                var diff_top = pos.top - dot_top_st

                var host_width_now  = 0
                var host_height_now = 0
                var host_left_now   = 0
                var host_top_now    = 0
                if (el_type == 'dot-cover-right-center' || el_type == 'dot-cover-bottom-center') {
                    // 右中, 下中
                    
                    host_left_now   = host_left_st
                    host_top_now    = host_top_st
                    host_width_now  = host_width_st + diff_left
                    host_height_now = host_height_st + diff_top
                } else if (el_type == 'dot-cover-left-center' || el_type == 'dot-cover-top-center') {
                    // 左中、上中

                    host_left_now   = host_left_st    + diff_left
                    host_top_now    = host_top_st     + diff_top
                    host_width_now  = host_width_st   - diff_left
                    host_height_now = host_height_st  - diff_top
                } else {
                    // 左上、右上、右下、左下

                    // 以横边的缩放比来计算
                    diff_left = diff_left
                    diff_top = diff_left / ht_aspect_ratio

                    // 左上
                    if (el_type == 'dot-cover-top') {

                        host_width_now  = host_width_st  - diff_left
                        host_height_now = host_height_st - diff_top
                        
                        host_left_now   = host_left_st  + diff_left
                        host_top_now    = host_top_st   + diff_top
                    }

                    // 右上
                    if (el_type == 'dot-cover-right') {

                        host_width_now  = host_width_st  + diff_left
                        host_height_now = host_height_st + diff_top

                        host_left_now   = host_left_st
                        host_top_now    = host_top_st - diff_top
                    }

                    // 右下
                    if (el_type == 'dot-cover-bottom') {

                        host_width_now  = host_width_st  + diff_left
                        host_height_now = host_height_st + diff_top

                        host_left_now   = host_left_st
                        host_top_now    = host_top_st
                    }

                    // 左下
                    if (el_type == 'dot-cover-left') {

                        host_width_now  = host_width_st  - diff_left
                        host_height_now = host_height_st - diff_top

                        host_left_now   = host_left_st + diff_left
                        host_top_now    = host_top_st
                    }
                }


                // 设置宿主宽高、位置
                $host_now.css({
                    width:  host_width_now,
                    height: host_height_now,
                    left:   host_left_now,
                    top:    host_top_now,
                })


                // 设置边框线的位置, 需加上模块的偏移量
                $lines.css({
                    left:   ht_parent_pos.left  + host_left_now,
                    top:    ht_parent_pos.top   + host_top_now,
                    width:  host_width_now,
                    height: host_height_now,
                })


                if (onMoveCallback instanceof Function) {

                    onMoveCallback({
                        $host:  $host_now,
                        width:  host_width_now,
                        height: host_height_now,
                        left:   host_left_now,
                        top:    host_top_now,
                    })
                }
            },


            // 结束拖拽时
            onEnd: function ($el) {
                                
                // 清除行内样式, 让拖拽点恢复默认位置
                $el.attr('style', '')
            }
        }
        Drag($dot_top,              Object.assign({ axis: 'all' },  drag_config))
        Drag($dot_top_center,       Object.assign({ axis: 'y' },    drag_config))
        Drag($dot_right,            Object.assign({ axis: 'all' },  drag_config))
        Drag($dot_right_center,     Object.assign({ axis: 'x' },    drag_config))
        Drag($dot_bottom,           Object.assign({ axis: 'all' },  drag_config))
        Drag($dot_bottom_center,    Object.assign({ axis: 'y' },    drag_config))
        Drag($dot_left,             Object.assign({ axis: 'all' },  drag_config))
        Drag($dot_left_center,      Object.assign({ axis: 'x' },    drag_config))


        // 显示边框线
        var show = function ($host, parent_pos) {

            parent_pos = parent_pos || {top: 0, left: 0}

            var host_pos = $host.position()
            var host_top = host_pos.top
            var host_left = host_pos.left
            var host_width = $host.width()
            var host_height = $host.height()

            var parent_top = parent_pos.top
            var parent_left = parent_pos.left

            $lines.css({
                width:  host_width,
                height: host_height,
                top:    parent_top  + host_top,
                left:   parent_left + host_left,
            })


            // 设置当前执行环境需要的参数
            $host_now       = $host
            ht_parent_pos   = parent_pos

            // 执行
            $lines.show()
        }


        // 隐藏边框线
        var hide = function () {

            $lines.hide()
        }

        return {
            show: show,
            hide: hide,
        }
    }


    // 模块边框线, 具体见BoxLine注释
    var mkBoxLine = BoxLine({

        $lines: $mkResizeLines,
        // 隐藏其他拖拽点, 只保留从下往上的拖拽
        disable_dots: ['top', 'top-center', 'right', 'right-center', 'bottom', 'left', 'left-center'],
        onMove: function (param) {
            
            // 模块是固定定位, 不需要top值, 取消top值
            param.$host.css('top', 0)
            
            // 触发事件
            util.observer.emit(EV_EDITOR_HEIGHT_CHANGE, $entity.height())
        }
    })


    /**
     * 组件边框线
     * 边框线DOM交互和逻辑具体见BoxLine注释
     * 该闭包主要做1件事, 当边框线尺寸变化时, 判断是否它的宿主DOM是否为图片组件, 如果是同时更新图片组件的尺寸
     * 返回 BoxLine 的执行结果
     * 此方法更多解释见README.md文档
     */
    var componentBoxLine = (function () {

        // 图片与所在组件div的大小比例,  比例 = 图片宽 / 组件div宽,  图片宽 = 组件div宽 * 比例,  组件div宽 = 图片宽 / 比例
        var width_scale = 1
        var height_scale = 1

        // left和top与图片的比例,  比例 = left / 图片宽,  left = 图片宽 * 比例,  图片宽 = left / 比例;  高的计算同理
        var left_width_scale = 1
        var top_height_scale = 1

        return BoxLine({

            $lines: $componentResizeLines,
            onStart: function ($component) {

                /**
                 * 如果当前组件时图片组件, 需要内部图片跟随组件变化而变化, 包括宽高和定位
                 * 宽高定位都是通过在移动前算出比例,　然后移动中实时计算赋值的
                 * 
                 * 宽高计算原理：假设图片为 100 * 100, 组件div是 200 * 200, 此时的图片与组件div比例是 1:2 , 当组件div缩小10px时, 对应的图片缩小 5px
                 * 定位计算原理：为方便思考, 假设此时图片相对于组件div的left为-10px, 图片是 100 * 100, 组件div是 200 * 200 , 
                 *              此时图片的left值与图片的宽比例是 -0.1 , 当组件div缩小了10px, 图片就缩小5px, 可以算出此时图片的left值应该是 100 * -0.1 = 9.5;
                 *              top的计算同理
                 */
                if ($component.attr('el-type') === EL_TYPE_COMPONENT_PIC) {

                    // 避免变量名重复, 使用闭包包裹起来
                    ;(function () {

                        var $img = $component.find('img')
                                    
                        width_scale = $img.width() / $component.width()
                        height_scale = $img.height() / $component.height()
    
                        var img_pos = $img.position()
                        left_width_scale = img_pos.left / $img.width()
                        top_height_scale = img_pos.top / $img.height()
                    })()
                }
            },
            onMove: function (param) {

                // 如果是图片组件, 缩放时需同时缩放里面的图片
                if (param.$host.attr('el-type') === EL_TYPE_COMPONENT_PIC) {

                    // 避免变量名重复, 使用闭包包裹起来
                    ;(function () {
                        
                        var $component = param.$host

                        var width = param.width * width_scale
                        var height = param.height * height_scale
        
                        var left_now = width * left_width_scale
                        var top_now = height * top_height_scale
        
                        $component.find('img').css({
                            width: width,
                            height: height,
                            left: left_now,
                            top: top_now,
                        })
                    })()
                }
            }
        })
    })()


    /**
     * 裁剪边框线
     * 边框线DOM交互和逻辑具体见BoxLine注释
     * 该闭包主要做1件事, 当它的宿主DOM为图片组件时, 表示正在裁剪这张图片,
     * 此时缩放组件大小会让里面的 img 跟着一起移动, 所以该闭包给里面的 img 设置一个抵消值, 让整体看起来相对静止,
     * 此方法更多解释见README.md文档
     */
    var trimBoxLine = (function () {

        // 宿主起始位置
        var host_left_st = 0
        var host_top_st = 0
        // 里面图片起始位置
        var img_left_st = 0
        var img_top_st = 0
        
        return BoxLine({

            $lines: $trimResizeLines,
            onStart: function ($host) {
                
                // 只有图片组件才需要设置
                if (!util.isPicComponent($host)) return

                var host_pos = $host.position()
                host_left_st = host_pos.left
                host_top_st = host_pos.top

                var img_pos = $host.find('img').position()
                img_left_st = img_pos.left
                img_top_st = img_pos.top
            },
            onMove: function (param) {
                
                // 只有图片组件才需要设置
                if (!util.isPicComponent(param.$host)) return

                var $host = param.$host
                var $img = $host.find('img')
                var host_pos = $host.position()

                var diff_left = host_pos.left - host_left_st
                var diff_top = host_pos.top - host_top_st


                $img.css({
                    left: img_left_st - diff_left,
                    top: img_top_st - diff_top,
                })
            }
        })
    })()


    // 热区边框线
    var hotspotBoxLine = BoxLine({
        $lines: $hotspotResizeLines,
    })


    /**
     * 辅助线, 禁用参考线可以调用闭包暴露的disable方法, 或干脆不调用show方法
     * 此方法更多解释见README.md文档
     * @method show 显示并设置参考线
     * @method hide 隐藏所有参考线
     * @method enable 启用参考线
     * @method disable 禁用参考线
     */
    var guideLine = (function () {

        // 是否禁用参考线
        var is_disable = false


        // 获取组件相对与浏览器视口上下左右的距离
        var getPos = function ($component) {

            var pos = $component.position()
            var width = $component.outerWidth()
            var height = $component.outerHeight()

            var left = pos.left
            var top = pos.top
            var right = left + width
            var bottom = top + height

            return {
                left: left,
                top: top,
                right: right,
                bottom: bottom,
            }
        }


        /**
         * 显示并设置参考线位置
         * @param {Object} config
         *      - $mk           组件所在模块
         *      - $component    组件
         *      - threshold     阈值
         *      - topSucc       顶部对齐时的回调, 回调入参为顶部参考线的top值
         *      - rightSucc     右侧对齐时的回调, 回调入参为右侧参考线的left值
         *      - bottomSucc    下面对齐时的回调, 回调入参为下面参考线的top值
         *      - leftSucc      左侧对齐时的回调, 回调入参为左侧参考线的left值
         * 
         * - 此方法默认会把 $target 与 $mk下所有带有 el-type 自定义属性的DOM作对比, 即热区和热区可以对比, 热区和组件也可以做对比, 
         *   如果不希望热区和热区之间做对比, 则需修改$other_els变量, 只把组件DOM赋值给它
         */
        var show = function (config) {

            // 判断是否禁用参考线
            if (is_disable) {

                hide()
                return
            }

            var $mk = config.$mk
            var $target = config.$target
            var threshold = config.threshold      // 阈值
            threshold = threshold == null ? 10 : threshold
            threshold = isNaN(parseInt(threshold)) ? 10 : parseInt(threshold)

            // 当参考线匹配时执行的回调
            var topSucc     = (config.topSucc instanceof Function)    ? config.topSucc    : function () {}
            var rightSucc   = (config.rightSucc instanceof Function)  ? config.rightSucc  : function () {}
            var bottomSucc  = (config.bottomSucc instanceof Function) ? config.bottomSucc : function () {}
            var leftSucc    = (config.leftSucc instanceof Function)   ? config.leftSucc   : function () {}

            var $other_els = $mk.find('[el-type]')
            var cmt_now_pos = getPos($target)
            var mk_now_pos = $mk.position()

            // 上参考线的参数
            var top_param = {
                is_show: false,
                left: null,
                right: null,
                top: null,

                distance: null,
                distance_pre: null,
            }

            // 右参考线的参数
            var right_param = {
                is_show: false,
                top: null,
                bottom: null,
                left: null,

                distance: null,
                distance_pre: null,
            }
            
            // 下参考线的参数
            var bottom_param = {
                is_show: false,
                left: null,
                right: null,
                top: null,

                distance: null,
                distance_pre: null,
            }

            // 左参考线的参数
            var left_param = {
                is_show: false,
                top: null,
                bottom: null,
                left: null,

                distance: null,
                distance_pre: null,
            }

            // 上对下: 其他元素的上边对齐移动元素的下边
            var top_bottom_param = {
                is_show: false,
                left: null,
                right: null,
                top: null,
                
                distance: null,
                distance_pre: null,
            }

            // 右对左
            var right_left_param = {
                is_show: false,
                top: null,
                bottom: null,
                left: null,
            }

            // 下对上
            var bottom_top_param = {
                is_show: false,
                left: null,
                right: null,
                top: null,
            }

            // 左对右
            var left_right_param = {
                is_show: false,
                top: null,
                bottom: null,
                left: null,
            }

            $other_els.each(function (i, el) {

                // 如果是当前DOM则跳过
                if (el == $target[0]) return
                var $el = $(el)
                var el_pos = getPos($el)


                // 顶部
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.top - cmt_now_pos.top) > threshold) return


                    top_param.distance = Math.abs(el_pos.top - cmt_now_pos.top)
                    // 第一次循环, 直接赋值即可
                    if (top_param.distance_pre == null) {

                        top_param.left = el_pos.left
                        top_param.right = el_pos.right
                        top_param.top = el_pos.top
                        
                        top_param.distance_pre = top_param.distance
                        top_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (top_param.distance > top_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (top_param.top == el_pos.top) {

                        top_param.left = Math.min(el_pos.left, top_param.left)
                        top_param.right = Math.max(el_pos.right, top_param.right)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (top_param.distance < top_param.distance_pre) {

                        top_param.left = el_pos.left
                        top_param.right = el_pos.right
                        top_param.top = el_pos.top
                        
                        top_param.distance_pre = top_param.distance
                    }
                })()

                // 右侧
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.right - cmt_now_pos.right) > threshold) return
                    

                    right_param.distance = Math.abs(el_pos.right - cmt_now_pos.right)
                    // 第一次循环, 直接赋值即可
                    if (right_param.distance_pre == null) {

                        right_param.top = el_pos.top
                        right_param.bottom = el_pos.bottom
                        right_param.left = el_pos.right
                        
                        right_param.distance_pre = right_param.distance
                        right_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (right_param.distance > right_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (right_param.left == el_pos.right) {

                        right_param.top = Math.min(el_pos.top, right_param.top)
                        right_param.bottom = Math.max(el_pos.bottom, right_param.bottom)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (right_param.distance < right_param.distance_pre) {

                        right_param.top = el_pos.top
                        right_param.bottom = el_pos.bottom
                        right_param.left = el_pos.right

                        right_param.distance_pre = right_param.distance
                    }
                })()
                
                // 下面
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.bottom - cmt_now_pos.bottom) > threshold) return


                    bottom_param.distance = Math.abs(el_pos.bottom - cmt_now_pos.bottom)
                    // 第一次循环, 直接赋值即可
                    if (bottom_param.distance_pre == null) {

                        bottom_param.left = el_pos.left
                        bottom_param.right = el_pos.right
                        bottom_param.top = el_pos.bottom
                        
                        bottom_param.distance_pre = bottom_param.distance
                        bottom_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (bottom_param.distance > bottom_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (bottom_param.top == el_pos.bottom) {

                        bottom_param.left = Math.min(el_pos.left, bottom_param.left)
                        bottom_param.right = Math.max(el_pos.right, bottom_param.right)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (bottom_param.distance < bottom_param.distance_pre) {

                        bottom_param.left = el_pos.left
                        bottom_param.right = el_pos.right
                        bottom_param.top = el_pos.bottom
                        
                        bottom_param.distance_pre = bottom_param.distance
                    }
                })()

                // 左侧
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.left - cmt_now_pos.left) > threshold) return
                    

                    left_param.distance = Math.abs(el_pos.left - cmt_now_pos.left)
                    // 第一次循环, 直接赋值即可
                    if (left_param.distance_pre == null) {

                        left_param.top = el_pos.top
                        left_param.bottom = el_pos.bottom
                        left_param.left = el_pos.left
                        
                        left_param.distance_pre = left_param.distance
                        left_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (left_param.distance > left_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (left_param.left == el_pos.left) {

                        left_param.top = Math.min(el_pos.top, left_param.top)
                        left_param.bottom = Math.max(el_pos.bottom, left_param.bottom)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (left_param.distance < left_param.distance_pre) {

                        left_param.top = el_pos.top
                        left_param.bottom = el_pos.bottom
                        left_param.left = el_pos.left

                        left_param.distance_pre = left_param.distance
                    }
                })()

                // 上对下
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.top - cmt_now_pos.bottom) > threshold) return


                    top_bottom_param.distance = Math.abs(el_pos.top - cmt_now_pos.top)
                    // 第一次循环, 直接赋值即可
                    if (top_bottom_param.distance_pre == null) {

                        top_bottom_param.left = el_pos.left
                        top_bottom_param.right = el_pos.right
                        top_bottom_param.top = el_pos.top
                        
                        top_bottom_param.distance_pre = top_bottom_param.distance
                        top_bottom_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (top_bottom_param.distance > top_bottom_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (top_bottom_param.top == el_pos.top) {

                        top_bottom_param.left = Math.min(el_pos.left, top_bottom_param.left)
                        top_bottom_param.right = Math.max(el_pos.right, top_bottom_param.right)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (top_bottom_param.distance < top_bottom_param.distance_pre) {

                        top_bottom_param.left = el_pos.left
                        top_bottom_param.right = el_pos.right
                        top_bottom_param.top = el_pos.top
                        
                        top_bottom_param.distance_pre = top_bottom_param.distance
                    }
                })()

                // 右对左
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.right - cmt_now_pos.left) > threshold) return


                    right_left_param.distance = Math.abs(el_pos.right - cmt_now_pos.left)
                    // 第一次循环, 直接赋值即可
                    if (right_left_param.distance_pre == null) {

                        right_left_param.top = el_pos.top
                        right_left_param.bottom = el_pos.bottom
                        right_left_param.left = el_pos.right
                        
                        right_left_param.distance_pre = right_left_param.distance
                        right_left_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (right_left_param.distance > right_left_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (right_left_param.left == el_pos.right) {

                        right_left_param.top = Math.min(el_pos.top, right_left_param.top)
                        right_left_param.bottom = Math.max(el_pos.bottom, right_left_param.bottom)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (right_left_param.distance < right_left_param.distance_pre) {

                        right_left_param.top = el_pos.top
                        right_left_param.bottom = el_pos.bottom
                        right_left_param.left = el_pos.right
                        
                        right_left_param.distance_pre = right_left_param.distance
                    }
                })()

                // 下对上
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.bottom - cmt_now_pos.top) > threshold) return


                    bottom_top_param.distance = Math.abs(el_pos.bottom - cmt_now_pos.top)
                    // 第一次循环, 直接赋值即可
                    if (bottom_top_param.distance_pre == null) {

                        bottom_top_param.left = el_pos.left
                        bottom_top_param.right = el_pos.right
                        bottom_top_param.top = el_pos.bottom
                        
                        bottom_top_param.distance_pre = bottom_top_param.distance
                        bottom_top_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (bottom_top_param.distance > bottom_top_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (bottom_top_param.top == el_pos.bottom) {

                        bottom_top_param.left = Math.min(el_pos.left, bottom_top_param.left)
                        bottom_top_param.right = Math.max(el_pos.right, bottom_top_param.right)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (bottom_top_param.distance < bottom_top_param.distance_pre) {

                        bottom_top_param.left = el_pos.left
                        bottom_top_param.right = el_pos.right
                        bottom_top_param.top = el_pos.bottom
                        
                        bottom_top_param.distance_pre = bottom_top_param.distance
                    }
                })()

                // 左对右
                ;(function () {

                    // 距离未在阈值之内, 跳过
                    if (Math.abs(el_pos.left - cmt_now_pos.right) > threshold) return


                    left_right_param.distance = Math.abs(el_pos.left - cmt_now_pos.right)
                    // 第一次循环, 直接赋值即可
                    if (left_right_param.distance_pre == null) {

                        left_right_param.top = el_pos.top
                        left_right_param.bottom = el_pos.bottom
                        left_right_param.left = el_pos.left
                        
                        left_right_param.distance_pre = left_right_param.distance
                        left_right_param.is_show = true
                        return
                    }


                    // 第二次循环起需判断3种情况
                    // 1. 当前DOM与移动元素的距离比上一个的大, 直接跳过即可
                    if (left_right_param.distance > left_right_param.distance_pre) return


                    // 2. 上一次DOM与移动元素的距离与本次循环相等(那么distance == distance_pre), 需比较取出两个最边界的值
                    if (left_right_param.left == el_pos.left) {

                        left_right_param.top = Math.min(el_pos.top, left_right_param.top)
                        left_right_param.bottom = Math.max(el_pos.bottom, left_right_param.bottom)
                    }


                    // 3. 本次DOM与移动元素的距离小于之前的, 那表明当前DOM在另一条线上, 需重新赋值(不用比较取最边界值, 那样计算出来的线的长度不正确)
                    if (left_right_param.distance < left_right_param.distance_pre) {

                        left_right_param.top = el_pos.top
                        left_right_param.bottom = el_pos.bottom
                        left_right_param.left = el_pos.left
                        
                        left_right_param.distance_pre = left_right_param.distance
                    }
                })()
            })


            // 上参考线, 处理上对上、下对上的对齐
            ;(function () {

                // 如果上对上, 下对上都无结果提前退出
                if (!top_param.is_show && !bottom_top_param.is_show) {
                    $guide_line_top.hide()
                    return
                }

                // 参考线的参数
                var top = null
                var left = null
                var right = null
                var width = null

                // 上对上, 下对上同时存在的情况
                if (top_param.is_show && bottom_top_param.is_show) {

                    // 看离那个比较近就按那个来设置
                    if (top_param.top == bottom_top_param.top) {
                        // 上下两个相等

                        left = Math.min(top_param.left, bottom_top_param.left, cmt_now_pos.left)
                        right = Math.max(top_param.right, bottom_top_param.right, cmt_now_pos.right)
                        top = top_param.top
                    } else if (top_param.distance < bottom_top_param.distance) {
                        // 上对上

                        left = Math.min(top_param.left, cmt_now_pos.left)
                        right = Math.max(top_param.right, cmt_now_pos.right)
                        top = top_param.top
                    } else {
                        // 下对上

                        left = Math.min(bottom_top_param.left, cmt_now_pos.left)
                        right = Math.max(bottom_top_param.right, cmt_now_pos.right)
                        top = bottom_top_param.top
                    }

                    width = right - left
                } else if (top_param.is_show) {
                    // 上对上

                    left = Math.min(top_param.left, cmt_now_pos.left)
                    right = Math.max(top_param.right, cmt_now_pos.right)
                    top = top_param.top
                    width = right - left
                } else {
                    // 下对上

                    left = Math.min(bottom_top_param.left, cmt_now_pos.left)
                    right = Math.max(bottom_top_param.right, cmt_now_pos.right)
                    top = bottom_top_param.top
                    width = right - left
                }

                // 设置宽和定位
                $guide_line_top.css({
                    top:   top + mk_now_pos.top,
                    left:  left + mk_now_pos.left,
                    width: width,
                }).show()

                // 返回上边距离顶部的距离
                topSucc(top)
            })()


            // 右参考线, 处理右对右、左对右的对齐
            ;(function () {

                // 如果右对右, 左对右都无结果提前退出
                if (!right_param.is_show && !left_right_param.is_show) {
                    $guide_line_right.hide()
                    return
                }

                // 参考线的参数
                var left = null
                var top = null
                var bottom = null
                var height = null

                // 左对左, 右对左同时存在的情况
                if (right_param.is_show && left_right_param.is_show) {

                    // 看离那个比较近就按那个来设置
                    if (right_param.left == left_right_param.left) {
                        // 左右两个相等
                        
                        top = Math.min(right_param.top, left_right_param.top, cmt_now_pos.top)
                        bottom = Math.max(right_param.bottom, left_right_param.bottom, cmt_now_pos.bottom)
                        left = right_param.left
                    } else if (right_param.distance < left_right_param.distance) {
                        // 右对右

                        top = Math.min(right_param.top, cmt_now_pos.top)
                        bottom = Math.max(right_param.bottom, cmt_now_pos.bottom)
                        left = right_param.left
                    } else {
                        // 左对右

                        top = Math.min(left_right_param.top, cmt_now_pos.top)
                        bottom = Math.max(left_right_param.bottom, cmt_now_pos.bottom)
                        left = left_right_param.left
                    }

                    height = bottom - top
                } else if (right_param.is_show) {
                    // 右对右

                    top = Math.min(right_param.top, cmt_now_pos.top)
                    bottom = Math.max(right_param.bottom, cmt_now_pos.bottom)
                    left = right_param.left
                    height = bottom - top
                } else {
                    // 右对左

                    top = Math.min(left_right_param.top, cmt_now_pos.top)
                    bottom = Math.max(left_right_param.bottom, cmt_now_pos.bottom)
                    left = left_right_param.left
                    height = bottom - top
                }

                // 设置高和定位
                $guide_line_right.css({
                    left: left + mk_now_pos.left,
                    top: top + mk_now_pos.top,
                    height: height,
                }).show()

                // 返回右边距离左侧的距离
                rightSucc(left)
            })()


            // 下参考线, 处理下对下、上对下的对齐
            ;(function () {

                // 如果下对下, 上对下都无结果提前退出
                if (!bottom_param.is_show && !top_bottom_param.is_show) {
                    $guide_line_bottom.hide()
                    return
                }

                // 参考线参数
                var top = null
                var left = null
                var right = null
                var width = null

                // 下对下, 上对下同时存在的情况
                if (bottom_param.is_show && top_bottom_param.is_show) {

                    // 看离那个比较近就按那个来设置
                    if (bottom_param.top == top_bottom_param.top) {
                        // 上下两个相等

                        left = Math.min(bottom_param.left, top_bottom_param.left, cmt_now_pos.left)
                        right = Math.max(bottom_param.right, top_bottom_param.right, cmt_now_pos.right)
                        top = bottom_param.top
                    } else if (bottom_param.distance < top_bottom_param.distance) {
                        // 下对下

                        left = Math.min(bottom_param.left, cmt_now_pos.left)
                        right = Math.max(bottom_param.right, cmt_now_pos.right)
                        top = bottom_param.top
                    } else {
                        // 上对下

                        left = Math.min(top_bottom_param.left, cmt_now_pos.left)
                        right = Math.max(top_bottom_param.right, cmt_now_pos.right)
                        top = top_bottom_param.top
                    }

                    width = right - left
                } else if (bottom_param.is_show) {
                    // 下对下

                    left = Math.min(bottom_param.left, cmt_now_pos.left)
                    right = Math.max(bottom_param.right, cmt_now_pos.right)
                    top = bottom_param.top
                    width = right - left
                } else {
                    // 上对下

                    left = Math.min(top_bottom_param.left, cmt_now_pos.left)
                    right = Math.max(top_bottom_param.right, cmt_now_pos.right)
                    top = top_bottom_param.top
                    width = right - left
                }


                // 设置宽和定位
                $guide_line_bottom.css({
                    top: top + mk_now_pos.top,
                    left: left + mk_now_pos.left,
                    width: width,
                }).show()

                // 返回下边距离顶部的距离
                bottomSucc(top)
            })()


            // 左参考线, 处理左对左、右对左的对齐
            ;(function () {

                // 如果左对左, 右对下都无结果提前退出
                if (!left_param.is_show && !right_left_param.is_show) {
                    $guide_line_left.hide()
                    return
                }

                // 参考线的参数
                var left = null
                var top = null
                var bottom = null
                var height = null

                // 左对左, 右对左同时存在的情况
                if (left_param.is_show && right_left_param.is_show) {

                    // 看离那个比较近就按那个来设置
                    if (left_param.left == right_left_param.left) {
                        // 左右两个相等
                        
                        top = Math.min(left_param.top, right_left_param.top, cmt_now_pos.top)
                        bottom = Math.max(left_param.bottom, right_left_param.bottom, cmt_now_pos.bottom)
                        left = left_param.left
                    } else if (left_param.distance < right_left_param.distance) {
                        // 左对左

                        top = Math.min(left_param.top, cmt_now_pos.top)
                        bottom = Math.max(left_param.bottom, cmt_now_pos.bottom)
                        left = left_param.left
                    } else {
                        // 右对左

                        top = Math.min(right_left_param.top, cmt_now_pos.top)
                        bottom = Math.max(right_left_param.bottom, cmt_now_pos.bottom)
                        left = right_left_param.left
                    }

                    height = bottom - top
                } else if (left_param.is_show) {
                    // 左对左

                    top = Math.min(left_param.top, cmt_now_pos.top)
                    bottom = Math.max(left_param.bottom, cmt_now_pos.bottom)
                    left = left_param.left
                    height = bottom - top
                } else {
                    // 右对左

                    top = Math.min(right_left_param.top, cmt_now_pos.top)
                    bottom = Math.max(right_left_param.bottom, cmt_now_pos.bottom)
                    left = right_left_param.left
                    height = bottom - top
                }


                // 设置高和定位
                $guide_line_left.css({
                    top: top + mk_now_pos.top,
                    left: left + mk_now_pos.left,
                    height: height,
                }).show()

                // 返回左边距离左侧的距离
                leftSucc(left)
            })()

        }
        

        // 隐藏参考线
        var hide = function () {

            $guide_line_top.hide()
            $guide_line_right.hide()
            $guide_line_bottom.hide()
            $guide_line_left.hide()
        }


        // 启用参考线
        var enable = function () { is_disable = false }


        // 禁用参考线
        var disable = function () { is_disable = true }


        return {
            show: show,
            hide: hide,
            enable: enable,
            disable: disable,
        }
    })()
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /* -------------------- 边框线、辅助线 end -------------------- */


    /**
     * 生成图片构造器, 该构造器接收1个DOM作为要生成图片的区域
     * 此方法更多解释见README.md文档
     * @param {DOM} entity 要生成图片的DOM
     * 
     * 执行该构造器后返回一个对象, 对象里的方法:
     * @method exec(config)  执行图片生成
     *      config对象包括:
     *          @config {Number} chunk_height    分片高度, 如果传入过高或非数值则该值等于entity的高度
     *          @config {Function} onStart       所有操作开始前调用, 只会执行一次, 如需终止返回一个 Promise.reject() 即可
     *          @config {Function} onChunk       分片图开始生成前调用, 每个分片生成前都会调用, 如需终止返回一个 Promise.reject() 即可
     *          @config {Function} onProgress(chunk_offset, total_length)  分片生成后调用, chunk_offset已生成的分片数, total_length 总分片数
     *          @config {Function} onComplete(base64_arr)  全部分片生成后调用, base64_arr 是所有图片的base64码
     *          @config {Function} onStop(base64_arr)      终止时调用, base64_arr 是已生成的图片的base64码, 终止后无法继续执行
     *          @config {Function} onError(err_links)      执行出错时调用, 目前只判断了图片加载失败时的错误, err_links 是加载失败的链接
     * @method stop(process_num)  终止指定进程号的图片生成
     */
    var GenerateImg = function (entity) {

        // 进程号当前值
        var process_num = 0
        // 正在进行中的进程号
        var process_arr = []


        /**
         * 检查图片是否正常加载, 
         * 通过判断图片的 naturalWidth和naturalHeight 是否和原图一样, 
         * 这两个属性在IE9以上可用、跟canvas一样, 可以放心使用
         */
        var isImageAllLoad = function (documentClone, $exec_now_imgs) {

            var $body = $(documentClone.querySelector('body'))
            var $clone_imgs = $body.find('#editor .entity').find('img')
            var err_links = []  // 记录错误加载失败的链接


            for (var i = 0, len = $clone_imgs.length; i < len; i++) {

                var isOk = false
                
                // 克隆图的src和真实宽高
                var clone_img = $clone_imgs.eq(i)[0]
                var clone_src = clone_img.src
                var clone_naturalWidth = clone_img.naturalWidth
                var clone_naturalHeight = clone_img.naturalHeight

                for (var j = 0, jlen = $exec_now_imgs.length; j < jlen; j++) {

                    // 原图的src和真实宽高
                    var exec_img = $exec_now_imgs.eq(j)[0]
                    var exec_src = exec_img.src
                    var exec_naturalWidth = exec_img.naturalWidth
                    var exec_naturalHeight = exec_img.naturalHeight

                    // 如果链接存在且宽高相同则表示加载成功
                    if (clone_src != exec_src) continue
                    if (clone_naturalWidth == exec_naturalWidth && clone_naturalHeight == exec_naturalHeight) isOk = true
                }

                // 记录失败的链接
                if (!isOk) err_links.push(clone_src)
            }


            return err_links
        }


        /**
         * 移除克隆DOM的热区标签
         * @param {DOM} documentClone 
         * 
         * @return 无
         */
        var removeCloneHotspot = function (documentClone) {

            var $body = $(documentClone.querySelector('body'))
            var $entity = $body.find('#editor .entity')

            $entity.find('[el-type="'+ EL_TYPE_HOTSPOT +'"]').remove()
        }


        // 执行
        var exec = function (param) {

            // exec实际的代码
            var fn = function (process_num, $exec_now_imgs) {
                // 入参
                param = param || {}
                param = (param.toString() === '[object Object]') ? param : {}
                var _onStart     = (param.onStart    instanceof Function) ? param.onStart    : function () {}  // 所有动作开始前
                var _onChunk     = (param.onChunk    instanceof Function) ? param.onChunk    : function () {}  // 分片图生成开始前
                var _onProgress  = (param.onProgress instanceof Function) ? param.onProgress : function () {}  // 进度, 即某个分片图生成后
                var _onChunkComplete = (param.onChunkComplete instanceof Function) ? param.onChunkComplete : function () {}  // 分片完成后调用
                var _onComplete  = (param.onComplete    instanceof Function) ? param.onComplete : function () {}  // 全部生成完毕后
                var _onStop      = (param.onStop    instanceof Function) ? param.onStop     : function () {}  // 全部生成完毕后
                var _onError     = (param.onError    instanceof Function) ? param.onError    : function () {}  // 生成图片失败
                var _chunk_pos   = param.chunk_pos

                
                // 当前编辑器高度
                var max_height = editorMethods.getHeight()


                // 确保chunk_pos是个数组, 且为这样的格式: [{ offset: 0, limit: 100 }, { offset: 100, limit: 200 }]
                var chunk_pos = (function () {

                    // 判断入参是否符合要求
                    if (_chunk_pos instanceof Array) {

                        var isOk = true
                        for (var i = 0, len = _chunk_pos.length; i < len; i++) {

                            var item = _chunk_pos[i]
                            if (item.toString() !== '[object Object]') {
                                
                                isOk = false
                                break
                            }

                            var offset = item.offset
                            var limit = item.limit
                            // 必须为数值
                            if (isNaN(offset) || isNaN(limit)) {

                                isOk = false
                                break
                            }
                            // 必须时整数
                            if (parseInt(offset) !== offset || parseInt(limit) !== limit) {

                                isOk = false
                                break
                            }
                            // offset大于0, limit大于等于1
                            if (offset < 0 || limit < 1) {

                                isOk = false
                                break
                            }
                        }


                        // 满足要求, 正常返回
                        if (isOk) return _chunk_pos
                    }

                    // 不符合要求, 默认一张图, 从头截到尾
                    return [{
                        offset: 0,
                        limit: max_height,
                    }]
                })()
                var chunk_pos_length = chunk_pos.length

                // 把函数返回结果转成Promise
                var toPromise = function (fn_result) {

                    return (fn_result instanceof Promise)
                        ? fn_result
                        : Promise.resolve(fn_result)
                }
                // 确保每个回调都是返回Promise实例
                var onStart     = function () { return toPromise(_onStart.apply(null, arguments)) }
                var onChunk     = function () { return toPromise(_onChunk.apply(null, arguments)) }
                var onProgress  = function () { return toPromise(_onProgress.apply(null, arguments)) }
                var onChunkComplete  = function () { return toPromise(_onChunkComplete.apply(null, arguments)) }
                var onComplete  = function () { return toPromise(_onComplete.apply(null, arguments)) }
                var onStop      = function () { return toPromise(_onStop.apply(null, arguments)) }
                var onError     = function () { return toPromise(_onError.apply(null, arguments)) }


                // base64数组
                var base64_arr = []

                // 当前生成的分片的下标
                var chunk_index = null

                // 递归调用, 使用 chunk_pos.shift() 取出下一个偏移量, 确保按顺序生成分片图
                function handle (chunk_pos_item) {
                
                    // 截图并生成base64码
                    var doHtml2canvas = function () {
                        
                        html2canvas(entity, {
                            // 不开启日志, 开了也没用??
                            logging: false,
                            // 允许加载跨域图片
                            useCORS: true,
                            // 从哪里开始截
                            y: chunk_pos_item.offset,
                            // 截多少
                            height: chunk_pos_item.limit,
                            /**
                             * html2canvas的执行逻辑是先把所有DOM克隆到一个临时的iframe里在从里面生成图片, 
                             * 该方法就是在克隆完成之后生成图片之前调用, 可以返回promise来控制是否生成图片
                             * @param {Document} documentClone iframe里的document
                             * @param {Document} referenceElement 原文档的document
                             * @returns void
                             */
                            onclone: function (documentClone, referenceElement) {

                                // onclone方法里返回Promise可以控制是否进行图片生成
                                return (new Promise(function (resolve, reject) {

                                    // 检查图片是否加载成功
                                    var err_links = isImageAllLoad(documentClone, $exec_now_imgs)
                                    
                                    // 有坏链, 终止
                                    if (err_links.length > 0) {

                                        onError(err_links)
                                        reject(err_links)
                                        return
                                    }

                                    
                                    // 移除克隆DOM里的热区标签
                                    removeCloneHotspot(documentClone)

                                    resolve()
                                }))
                            }
                        }).then(function(canvas) {

                            var base64_item = {

                                index: chunk_index,
                                width: canvas.width,
                                height: canvas.height,
                                base64: canvas.toDataURL("image/png"),
                            }

                            // 追加数据
                            base64_arr.push(base64_item)

                            // 进度回调, 传入已生成的图片长度、总长度、当前数组
                            onProgress(base64_arr.length, chunk_pos_length).catch(function (e) { /* 防止未捕获的错误 */ })

                            // 分片执行完成回调
                            onChunkComplete(chunk_pos_item, base64_item).catch(function (e) { /* 防止未捕获的错误 */ })
                            

                            if (chunk_pos.length == 0) {

                                // 调用回调, 传入分片64码数组
                                onComplete(base64_arr).catch(function (e) { /* 防止未捕获的错误 */ })
                            } else {
        
                                // 分片下标递增
                                chunk_index += 1
                                
                                // 接着下一个
                                handle(chunk_pos.shift())
                            }
                        })
                    }


                    // 分片图生成前回调, 只有回调resolve才会生成当前分片
                    onChunk().then(function () {

                        // 当前进程号是否已被终止, 已终止了就不在执行
                        if (process_arr.indexOf(process_num) == -1) {

                            onStop(base64_arr).then(function (e) { /* 防止未捕获的错误 */ })
                            return
                        }

                        // 正常执行
                        doHtml2canvas()

                    }).catch(function (e) {

                        // onChunk里reject即终止
                        onStop(base64_arr).then(function (e) { /* 防止未捕获的错误 */ })
                    })
                }


                // 开始前回调
                onStart().then(function () {

                    chunk_index = 0
                    handle(chunk_pos.shift())

                }).catch(function (e) { /* 防止未捕获的错误 */ })
            }


            // 当前进程号
            var process_now = process_num
            // 此次编辑器内容转图片时, 编辑器里的图片集合
            var $exec_now_imgs = $entity.find('img')
            // 执行
            fn(process_now, $exec_now_imgs)
            // 记录进程号
            process_arr.push(process_num)
            process_num += 1

            return process_now
        }


        // 终止正在生成的图片
        var stop = function (process_num) {

            var index = process_arr.indexOf(process_num)
            if (index == -1) return

            // 剔除进程号
            process_arr.splice(index, 1)
        }


        return {
            exec: exec,
            stop: stop,
        }
    }

    var generateImg = GenerateImg($entity[0])


    /* -------------------- 事件 -------------------- */
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    // 编辑器的事件
    var editorEvent = (function () {

        // 上一个被mousedown的组件
        var $component_pre = $()

        // 初始化
        return function () {

            // 侦听整个编辑器的mousedown, 相当于侦听编辑器下任何一个元素的mousedown
            // 主要是为了点击空白地方时隐藏边框线
            $editor.on('mousedown', function (e) {

                var $target = $(e.target)
                
                // 当前组件, 如果是
                var $component_now = util.isComponent($target)
                // 当前是热区, 如果是
                var $hotspot_now = util.isHotspot($target)
                // 当前边框线,　如果是
                var $boxLine = util.isBoxLine($target)

                
                // 不是组件、不是边框线、不是热区, 表明点了空白地方
                if (!$component_now && !$boxLine && !$hotspot_now) {
    
                    // 让文字组件失焦
                    $component_pre.find('div').blur()
    
                    // 让组件边框线, 裁剪边框线, 热区边框线隐藏
                    componentBoxLine.hide()
                    trimBoxLine.hide()
                    hotspotBoxLine.hide()

                    // 关闭可能存在的裁剪状态
                    groupMethods.picMethods.closeTrim()
    
                    return 
                }
    
    
                // 如果是边框线, 跳过无需其他操作. 表示目前可能正在缩放组件或裁剪图片
                if ($boxLine) return
    
    
                // 如果是组件
                if ($component_now) {
    
                    // 如果和上一个mousedown的组件不一样
                    if ($component_now[0] !== $component_pre[0]) {
    
                        // 让上一个组件失焦, 如果上一个组件是文字组件, 这行代码才会生效
                        $component_pre.find('div').blur()

                        // 关闭可能存在的裁剪状态
                        groupMethods.picMethods.closeTrim()
                    }
    
                    // 更新$component_pre
                    $component_pre = $component_now

                    // 隐藏热区边框线
                    hotspotBoxLine.hide()
                }
    
    
                // 如果是热区
                if ($hotspot_now) {

                    // 让文字组件失焦
                    $component_pre.find('div').blur()                   
    
                    // 隐藏组件边框线
                    componentBoxLine.hide()

                    // 重置$component_pre
                    $component_pre = $()
                }
            })
        }
    })()


    // 模块的事件
    var mkEvent = function ($el) {

        $el.on('mousedown', function () {

            // 显示模块边框线
            mkBoxLine.show($el)
        })
    }


    // group项的事件
    var groupEvent = (function () {

        // 设置辅助线
        var setGuideLine = function ($target, boxLine) {

            var $mk = $target.parents('[el-type="'+ EL_TYPE_MK +'"]').eq(0)
            var mk_pos = $mk.position()

            guideLine.show({
                $mk: $mk,
                $target: $target,
                
                // 阈值
                threshold: 5,

                // 顶部匹配时
                topSucc: function (top) {

                    $target.css('top', top + 'px')
                    boxLine.show($target, mk_pos)
                },
                // 右侧匹配
                rightSucc: function (left) {

                    left = left - $target.width()
                    $target.css('left', left + 'px')
                    boxLine.show($target, mk_pos)
                },
                // 下面匹配
                bottomSucc: function (top) {

                    top = top - $target.height()
                    $target.css('top', top + 'px')
                    boxLine.show($target, mk_pos)
                },
                // 左侧匹配
                leftSucc: function (left) {

                    $target.css('left', left + 'px')
                    boxLine.show($target, mk_pos)
                }
            })
        }


        // 图片组件事件
        // 此方法更多解释见README.md文档
        var picComponentEvent = function ($component) {

            // 所在模块
            var $mk = $component.parent('[el-type="mk"]')
            // 里面的图片
            var $img = $component.find('img')


            // 鼠标点下时, 显示组件边框线, 正在裁剪不给组件显示边框线
            $component.on('mousedown', function () {

                if ($component.data('is_triming')) return
                componentBoxLine.show($component, $mk.position())
            })


            // 鼠标点下时, 图片显示边框线, 只有在裁剪时显示边框线
            $img.on('mousedown', function () {

                if (!$component.data('is_triming')) return
                var offset = groupMethods.picMethods.getImgOffsetWhenTrim($component)
                componentBoxLine.show($img, offset)
            })

            
            // 判断是否为真实点击事件
            var isRealEvent = true
            // 给组件绑定拖拽
            $component.data('drag', Drag($component, {

                onStart: function ($el, is_real_event) {

                    isRealEvent = is_real_event
                },

                onMove: function () {

                    // 拖拽中重新显示边框线, show方法会重新定位边框线的位置
                    componentBoxLine.show($component, $mk.position())

                    // 设置辅助线的显示
                    if (isRealEvent) {

                        setGuideLine($component, componentBoxLine)
                    }
                },
                
                onEnd: function () {
                    
                    guideLine.hide()
                }
            }))


            // 给里面的图片绑定拖拽
            $img.data('drag', Drag($img, {

                onMove: function () {

                    var offset = groupMethods.picMethods.getImgOffsetWhenTrim($component)
                    // 拖拽中重新显示边框线, show方法会重新定位边框线的位置
                    componentBoxLine.show($img, offset)
                }
            }))
            // 默认禁止里面的图片移动
            $img.data('drag').disable()


            // 双击开启裁剪
            $component.on('dblclick', function () {

                groupMethods.picMethods.openTrim($component)
            })
        }


        // 文字组件的事件
        var textComponentEvent = function ($component) {

            // 所在模块
            var $mk = $component.parent('[el-type="mk"]')
            // 里面的div
            var $input = $component.find('div')


            // 判断是否为真实点击事件
            var isRealEvent = true
            // 绑定拖拽事件
            $component.data('drag', Drag($component, {

                // 不要禁止默认事件, 不然内部可编辑的div会不可编辑
                prevent_default: false,

                onStart: function (is_real_event) {

                    isRealEvent = is_real_event
                },

                onMove: function () {

                    // 拖拽中重新显示边框线, show方法会重新定位边框线的位置
                    componentBoxLine.show($component, $mk.position())

                    // 设置辅助线的显示
                    if (isRealEvent) {

                        setGuideLine($component, componentBoxLine)
                    }
                },

                onEnd: function () {

                    guideLine.hide()
                }
            }))


            // 鼠标点下显示组件边框线
            $component.on('mousedown', function () {
                
                var mk_pos = $mk.position()
                componentBoxLine.show($component, mk_pos)
            })


            /**
             * 文字组件需要能随内容自动跳转高度
             * 实现方式是在添加一个隐藏的textarea, 每当这个组件内容变换时更新到那个textarea里,
             * 然后拿textarea的高度设置这个组件的高度。注：textarea的除了高度, 其他样式与当前组件一样
             */

            // 聚焦时设置辅助input的样式, 让它于当前组件的$input相同
            $input.on('focus', function () {

                $auxiliary_input.attr('style', $input.attr('style'))
                $auxiliary_input.css({
                    display: 'none',
                    width: $component.width(),
                    height: 'auto'
                })
            })


            // 失焦时如果没有内容显示占位符内容
            $input.on('blur', function () {

                if ($input.val().length == 0) {

                    var placeholder = $input.attr('placeholder')
                    placeholder = placeholder || '请输入内容'
                    $input.attr('placeholder', placeholder)
                }
            })


            // 输入时更新组件高度
            $input.on('keyup', function (e) {

                $auxiliary_input.html($input.html())
                $component.height($auxiliary_input.height())
                
                // 更新边框线高度
                componentBoxLine.show($component, $mk.position())
            })


            // 防止input随内容高度自动滚动
            $input.on('scroll', function (e) {

                $input.scrollTop(0)
                e.preventDefault()
                return false
            })
        }


        // 热区的事件
        var hotspotEvent = function ($el) {


            $el.on('mousedown', function () {
                // 显示热区边框线
                var $mk = $el.parents('[el-type="'+ EL_TYPE_MK +'"]').eq(0)
                hotspotBoxLine.show($el, $mk.position())
            })

                
            // 给热区绑定拖拽
            $el.data('drag', Drag($el, {

                onMove: function () {

                    var $mk = $el.parents('[el-type="'+ EL_TYPE_MK +'"]')

                    // 拖拽中重新显示边框线, show方法会重新定位边框线的位置
                    hotspotBoxLine.show($el, $mk.position())

                    // 设置辅助线的显示
                    setGuideLine($el, hotspotBoxLine)
                },
                
                onEnd: function () {
                    
                    guideLine.hide()
                }
            }))
        }


        // 动图的事件
        var gifEvent = function ($el) {}


        // 视频的事件
        var videoEvent = function ($el) {}


        return function ($group_item) {

            switch ($group_item.data('source').type) {
                case COMPONENT_TYPE_PIC:
                    
                    picComponentEvent($group_item)
                    break
                case COMPONENT_TYPE_TEXT:
                    
                    textComponentEvent($group_item)
                    break
                case COMPONENT_TYPE_HOTSPOT:
                    
                    hotspotEvent($group_item)
                    break
                case COMPONENT_TYPE_GIF:
                    
                    gifEvent($group_item)
                    break
                case COMPONENT_TYPE_VIDEO:

                    videoEvent($group_item)
                    break
            }
        }
    })()
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /* -------------------- 事件 end -------------------- */


    /* -------------------- 方法 -------------------- */
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /**
     * 编辑器相关方法 
     * @method getHeight()      获取编辑器高度
     * @method getAllImgSrc()   获取编辑器下所有图片链接
     * @method getData()        获取编辑器数据, 该数据可用于提交给后端
     */
    var editorMethods = (function () {

        // 获取编辑器高度
        var getHeight = function () {

            return $entity.height()
        }


        // 获取编辑器中所有图片的链接地址
        var getAllImgSrc = function () {

            var res = []
            $entity.find('img').each(function (i, item) {

                res.push(item.src)
            })

            return res
        }


        // 获取整个编辑器的相关数据
        var getData = function () {

            var result = []
            var $mks = $entity.find('[el-type="'+ EL_TYPE_MK +'"]')

            for (var i = 0, len = $mks.length; i < len; i++) {

                var $el = $mks.eq(i)
                // 一个一个获取模块数据
                result.push(mkMethods.getData($el))
            }

            return result
        }


        return  {
            getHeight: getHeight,
            getAllImgSrc: getAllImgSrc,
            getData: getData,
        }
    })()


    /**
     * 模块相关的方法
     * @method trimIndex($mk)                   去掉组件z-index空挡
     * @method getMaxIndex($mk)                 获取模块下最大的z-index值
     * @method getData($mk)                     返回该模块的数据
     * @method copy($target_mk, $dist_mk, pos)  复制模块
     * @method cut($target_mk, $dist_mk, pos)   剪切模块
     * @method del($mk)                         删除模块
     * @method add(height, $dist_mk, pos)       添加模块
     * @method getMkByIndex(index)              通过下标获取模块
     */
    var mkMethods = (function () {

        /**
         * 初始化当前模块下组件的层级, 按顺序排列, 去掉空挡
         * 比如有4个组件, 它们的index分别是 2, 6, 9, 9, 0, 0  那么调用这个方法后变成 2, 3, 4, 4, 1, 1
         * 
         * @param {JQDOM} $mk 模块
         */
        var trimIndex = function ($mk) {

            var $components = $mk.find('[el-type^="component"]')

            // 给没有z-index属性的添加一个默认值, 该值是当前模块下的最小值 - 1
            ;(function () {

                // 取出index值生成一个数组
                var index_arr = []
                var cur_zindex = null
                for (var i = 0, len = $components.length; i < len; i++) {

                    cur_zindex = parseInt($($components[i]).css('z-index'))
                    if (!isNaN(cur_zindex)) {

                        index_arr.push(cur_zindex)
                    }
                }
                
                // 找到最小的值, 该值 - 1就是要设置的index
                index_arr.sort(function (a, b) { return a - b })
                var min_index = index_arr[0] - 1

                // 给没有z-index的设置该值
                var $cur_el = null
                for (var i = 0, len = $components.length; i < len; i++) {

                    $cur_el = $($components[i])
                    if (isNaN(parseInt($cur_el.css('z-index')))) {

                        $cur_el.css('z-index', min_index)
                    }
                }
            })()


            // 从1开始给组件设置z-index属性
            ;(function () {

                // 按index升序排序
                $components.sort(function (a, b) {

                    return parseInt($(a).css('z-index')) - parseInt($(b).css('z-index'))
                })

                // 从1开始递增
                var pre_old_index = null
                var new_index = 0
                $components.each(function (i, el) {

                    var $el = $(el)
                    var z_index = parseInt($el.css('z-index'))

                    if (z_index !== pre_old_index) {

                        new_index += 1
                        $el.css('z-index', new_index)
                        pre_old_index = z_index
                    } else {

                        $el.css('z-index', new_index)
                        pre_old_index = z_index
                    }
                })
            })()
        }


        /**
         * 获取模块下组件的最大z-index值
         * @param {JQDOM} $mk 
         * @returns {Number} 
         */
        var getMaxIndex = function ($mk) {

            // 取出所有组件的z-index值
            var $components = $mk.find('[el-type^="component"]')
            var index_arr = $.map($components, function (el) {

                return parseInt($(el).css('z-index'))
            })

            // 排序, 取最大一个
            index_arr.sort(function (a, b) { return a - b })
            
            return index_arr.pop()
        }


        /**
         * 获取模块数据
         * @param {JQDOM} $mk 
         * @returns {Object} { boxStyle, groups, category, hotspot }
         */
        var getData = function ($mk) {

            var $components = $mk.find('[el-type^="component"]')
            var $hotspot = $mk.find('[el-type="'+ EL_TYPE_HOTSPOT +'"]')

            // $groupDOM集合
            var $groups = $()
            $groups = $groups.add($components)
            $groups = $groups.add($hotspot)

            // 盒子
            var boxStyle = {
                "width": $mk.width() + 'px',
                "height": $mk.height() + 'px',
                "background-color": $mk.css('background-color'),
                "background-image": $mk.css('background-image'),
                "background-repeat": $mk.css('background-repeat'),
            }

            // group项的集合
            var groups = []
            $groups.each(function (i, el) {

                groups.push(groupMethods.getData($(el)))
            })

            // 暂时这样设置
            var category = 0


            return {
                boxStyle: boxStyle,
                groups: groups,
                category: category,
            }
        }


        /**
         * 复制模块
         * @param {JQDOM} $target_mk    要复制的模块
         * @param {JQDOM} $dist_mk      要复制到哪个模块的前面或后面
         * @param {String} pos          before或after, before表示复制到 $dist_mk前面, after表示复制到它后面
         * @returns {JQDOM} $el         新复制的模块
         */
        var copy = function ($target_mk, $dist_mk, pos) {

            pos = ['before', 'after'].indexOf(pos) == -1
                ? 'after'
                : pos

            var mk_data = getData($target_mk)
            var $el = renderMk(mk_data)

            // 选择要插入在目的模块的前面还是后面
            switch (pos) {
                case 'before':
                    
                    $dist_mk.before($el)
                    break
                case 'after':
                    
                    $dist_mk.after($el)
                    break
            }

            // 让新模块聚焦
            $el.mousedown()

            return $el
        }


        /**
         * 剪切模块
         * @param {JQDOM} $target_mk    要剪切的模块
         * @param {JQDOM} $dist_mk      要剪切到哪个模块的前面或后面
         * @param {String} pos          before或after, before表示剪切到 $dist_mk前面, after表示剪切到它后面
         */
        var cut = function ($target_mk, $dist_mk, pos) {

            // 复制模块
            var $newly_mk = copy($target_mk, $dist_mk, pos)

            // 删除原模块
            $target_mk.remove()

            // 删除模块后高度会瞬减, 导致模块边框线位置计算错误, 再次调用 mousedown 即可触发重新计算
            $newly_mk.mousedown()
        }


        /**
         * 删除模块
         * @param {JQDOM} $mk 
         */
        var del = function ($mk) {

            $mk.remove()

            // 隐藏组件边框线
            componentBoxLine.hide()
            // 隐藏辅助线
            guideLine.hide()
            // 隐藏模块边框线
            mkBoxLine.hide()
            // 隐藏热区边框线
            hotspotBoxLine.hide()

            // 触发事件
            util.observer.emit(EV_EDITOR_HEIGHT_CHANGE, $entity.height())
        }


        /**
         * 新增模块
         * @param {Number} height   模块的高度, 默认为300px
         * @param {JQDOM} $dist_mk  要插入到哪个模块后面或前面, 为空表示追加到编辑器最后面
         * @param {String} pos      要插入的位置, before表示插入到$dist_mk前面, after插入到它后面
         */
        var add = function (height, $dist_mk, pos) {

            // 新增模块默认高度为300px
            height = isNaN(parseInt(height)) ? 300 : parseInt(height)
            // 先确保$dist_mk是个jQ对象
            $dist_mk = ($dist_mk instanceof $) ? $dist_mk : $()
            // 再确保$dist_mk是编辑器的元素
            $dist_mk = $entity.find($dist_mk).length > 0 ? $dist_mk : null
            // 要插入的位置, before或after, 默认为after
            pos = ['before', 'after'].indexOf(pos) == -1 ? 'after' : pos

            // 模块默认数据
            var mk_data = mkDefaultData()
            mk_data.boxStyle.height = height + 'px'

            // 生成模块
            var $el = renderMk(mk_data)
            
            // 如果$dist_mk不存在则追加到编辑器末尾, 如果存在则看情况追加
            if ($dist_mk == null) {

                $entity.append($el)
            } else {

                switch (pos) {
                    case 'before':
                        $dist_mk.before($el)
                        break
                    case 'after':
                        $dist_mk.after($el)
                        break
                }
            }

            // 使模块聚焦
            $el.mousedown()

            // 触发事件
            util.observer.emit(EV_EDITOR_HEIGHT_CHANGE, $entity.height())

            // 返回该模块
            return $el
        }

        
        // 根据下标获取对应模块
        var getMkByIndex = function (index) {

            return $entity.find('[el-type="' + EL_TYPE_MK + '"]').eq(index)
        }


        // 设置模块的高度
        var setHeight = function ($mk, height) {

            height = util.toInt(height, MK_MIN_HEIGHT)
            $mk.height(height)

            // 更新模块边框线高度
            mkBoxLine.show($mk)
            // 触发高度变换事件
            util.observer.emit(EV_EDITOR_HEIGHT_CHANGE, $entity.height())
        }

        return {
            trimIndex: trimIndex,
            getMaxIndex: getMaxIndex,
            getData: getData,
            copy: copy,
            cut: cut,
            del: del,
            add: add,
            getMkByIndex: getMkByIndex,
            setHeight: setHeight,
        }
    })()


    /**
     * group项相关方法
     * @method centerGroupItem($group_item, axis) 居中group项
     * @method goUp($component)   上浮组件
     * @method goDown($component) 下沉组件
     * @method getData($group)    获取group项的数据
     * @method copy($target, $dist_mk, left, top)  复制group项
     * @method cut($target, $dist_mk, left, top)   剪切group项
     * @method del($group_item)  删除group项
     * @object textMethods  文字组件相关方法
     * @object picMethods   图片组件相关方法
     * @object hotspotMethods  热区组件相关方法
     */
    var groupMethods = (function () {

        /* --- 公用方法 --- */
        /* --- */
        /* --- */
        /**
         * 删除组件
         * @param {JQDOM} $component 
         */
        var del = function ($group_item) {

            // 移除DOM
            $group_item.remove()
            // 隐藏组件边框线
            componentBoxLine.hide()
            // 隐藏热区边框线
            hotspotBoxLine.hide()
            // 隐藏辅助线
            guideLine.hide()
        }


        /**
         * 居中组件
         * @param {JQDOM} $mk           组件所在模块
         * @param {JQDOM} $component    要居中的组件
         * @param {*} axis              居中哪个轴, x、y、all, x即左右居中, y即上下居中, all则中心居中
         * 
         * @returns void
         */
        var centerGroupItem = function ($group_item, axis) {

            var $mk = $group_item.parent('[el-type="mk"]')
            var pos = util.getCenterPos({
                parent_width: $mk.width(),
                parent_height: $mk.height(),
                target_width: $group_item.width(),
                target_height: $group_item.height(),
                left: 'auto',
                top: 'auto',
            })

            if (axis == 'y') {

                $group_item.css('top', pos.top + 'px')
            } else if (axis == 'x') {
                
                $group_item.css('left', pos.left + 'px')
            } else {
                
                $group_item.css({
                    top: pos.top,
                    left: pos.left,
                })
            }

            $group_item.mousedown()
        }


        /**
         * 组件层级向上1级, 即 z-index += 1
         * @param {JQDOM} $component
         */
        var goUp = function ($component) {

            // 非组件不能上浮
            if (!util.isComponent($component)) return

            var $mk = $component.parents('[el-type="'+ EL_TYPE_MK +'"]')
            var z_index = $component.css('z-index')
            $component.css('z-index', parseInt(z_index) + 1)

            // 使当前组件聚焦
            $component.mousedown()

            // 使热区置顶
            util.nextTick(function () { hotspotMethods.allToTop($mk) })
        }


        /**
         * 组件层级向上1级, 即 z-index -= 1
         * @param {JQDOM} $component 
         */
        var goDown = function ($group) {

            // 非组件不能下沉
            if (!util.isComponent($group)) return

            var $mk = $group.parents('[el-type="'+ EL_TYPE_MK +'"]')
            var z_index = parseInt($group.css('z-index')) - 1
            z_index = z_index > 0 ? z_index : 0
            $group.css('z-index', z_index)

            // 使当前组件聚焦
            $group.mousedown()

            // 使热区置顶
            util.nextTick(function () { hotspotMethods.allToTop($mk) })
        }


        /**
         * 获取图片组件的数据结构
         * @param {JQDOM} $component
         * @returns {Object} { boxStyle, textStyle, picStyle, type }
         */
        var getPicData = function ($component) {

            var $img = $component.find('img')
            var source = $component.data('source')

            // 盒子样式
            var component_pos = $component.position()
            var boxStyle = {
                "width": $component.width() + 'px',
                "height": $component.height() + 'px',
                "top": component_pos.top + 'px',
                "left": component_pos.left + 'px',
                "z-index": $component.css('z-index'),
                "border-radius": $component.css('border-radius'),
                
                // 暂时这样设置
                "guding": 0,
                "biaoji": source.boxStyle.biaoji,
                "type": source.boxStyle.type || ''
            }
            var textStyle = {}
            var hotspotStyle = {}
            var gifStyle = {}
            var videoStyle = {}

            // 图片组件样式
            var img_pos = $img.position()
            var picStyle = {
                "width": $img.width() + 'px',
                "height": $img.height() + 'px',
                "top": img_pos.top,
                "left": img_pos.left,
                "src": $img.attr('src'),

                // 暂时这样设置
                "scaling_width": source.picStyle.scaling_width,
                "scaling_height": source.picStyle.scaling_height,
            }

            var type = 'pic'

            return {
                boxStyle: boxStyle,
                textStyle: textStyle,
                picStyle: picStyle,
                hotspotStyle: hotspotStyle,
                gifStyle: gifStyle,
                videoStyle: videoStyle,
                type: type,
            }
        }


        /**
         * 获取文字组件的数据结构
         * @param {JQDOM} $component 
         * @returns {Object} { boxStyle, textStyle, picStyle, type }
         */
        var getTextData = function ($component) {

            var $div = $component.find('div')
            var source = $component.data('source')

            // 盒子样式
            var component_pos = $component.position()
            var boxStyle = {
                "width": $component.width() + 'px',
                "height": $component.height() + 'px',
                "top": component_pos.top,
                "left": component_pos.left,
                "z-index": $component.css('z-index'),
                "border-radius": $component.css('border-radius'),
                
                // 暂时这样写
                "guding": 0,
                "biaoji": source.boxStyle.biaoji,
                "type": "",
            }

            // 字体组件样式
            var textStyle = {
                "font-size": $div.css('font-size'),
                "font-family": $div.css('font-family'),
                "color": $div.css('color'),
                "text-align": $div.css('text-align'),
                "font-weight": $div.css('font-weight'),
                "background-color": $div.css('background-color'),
                "value": $div.html(),
                "font-style": $div.css('font-style'),
                "letter-spacing": $div.css('letter-spacing'),
                "line-height": $div.css('line-height'),
            }

            var picStyle = {}
            var hotspotStyle = {}
            var gifStyle = {}
            var videoStyle = {}
            var type = 'text'

            return {
                boxStyle: boxStyle,
                textStyle: textStyle,
                picStyle: picStyle,
                hotspotStyle: hotspotStyle,
                gifStyle: gifStyle,
                videoStyle: videoStyle,
                type: type,
            }
        }


        /**
         * 获取热区数据
         * @param {JQDOM} $hotspot 
         * @returns {Object} { boxStyle, textStyle, picStyle, type }
         */
        var getHotspotData = function ($hotspot) {
    
            // 盒子样式
            var pos = $hotspot.position()
            var width = $hotspot.width()
            var height = $hotspot.height()
            var boxStyle = {
                "width": width + 'px',
                "height": height + 'px',
                "left": pos.left + 'px',
                "top": pos.top + 'px',
                "z-index": $hotspot.css('z-index'),
                "border-radius": "0px",

                // 暂时这样写
                "guding": 0,
                "biaoji": 0,
                "type": "",
            }

            // 热区样式
            var hotspotStyle = {
                "link": $hotspot.find('div').data('link')
            }

            var picStyle = {}
            var textStyle = {}
            var gifStyle = {}
            var videoStyle = {}
            var type = 'hotspot'

            return {
                boxStyle: boxStyle,
                textStyle: textStyle,
                picStyle: picStyle,
                hotspotStyle: hotspotStyle,
                gifStyle: gifStyle,
                videoStyle: videoStyle,
                type: type,
            }
        }


        // 获取动图数据
        var getGifData = function ($gif) {

            // 盒子样式
            var pos = $gif.position()
            var width = $gif.width()
            var height = $gif.height()
            var boxStyle = {
                "width": width + 'px',
                "height": height + 'px',
                "left": pos.left + 'px',
                "top": pos.top + 'px',
                "z-index": $gif.css('z-index'),
                "border-radius": "0px",

                // 暂时这样写
                "guding": 0,
                "biaoji": 0,
                "type": "",
            }

            // 动图样式
            var gifStyle = {
                src: $gif.find('img').attr('src')
            }

            var picStyle = {}
            var textStyle = {}
            var hotspotStyle = {}
            var videoStyle = {}
            var type = 'gif'

            return {
                boxStyle: boxStyle,
                textStyle: textStyle,
                picStyle: picStyle,
                hotspotStyle: hotspotStyle,
                gifStyle: gifStyle,
                videoStyle: videoStyle,
                type: type,
            }
        }


        // 获取视频数据
        var getVideoData = function ($video) {

            // 盒子样式
            var pos = $video.position()
            var width = $video.width()
            var height = $video.height()
            var boxStyle = {
                "width": width + 'px',
                "height": height + 'px',
                "left": pos.left + 'px',
                "top": pos.top + 'px',
                "z-index": $video.css('z-index'),
                "border-radius": "0px",

                // 暂时这样写
                "guding": 0,
                "biaoji": 0,
                "type": "",
            }

            // 视频样式
            var videoStyle = {
                src: $video.find('video').attr('src')
            }
            
            var picStyle = {}
            var textStyle = {}
            var hotspotStyle = {}
            var gifStyle = {}
            var type = 'video'

            return {
                boxStyle: boxStyle,
                textStyle: textStyle,
                picStyle: picStyle,
                hotspotStyle: hotspotStyle,
                gifStyle: gifStyle,
                videoStyle: videoStyle,
                type: type,
            }
        }


        /**
         * 获取组件数据, 根据自定义属性 el-type 来判断要调用哪个子方法
         * @param {JQDOM} $component 
         * @returns 
         */
        var getData = function ($target) {

            var type = $target.attr('el-type')
            var data = null
            switch (type) {
                case EL_TYPE_COMPONENT_PIC:
                    data = getPicData($target)
                    break
                case EL_TYPE_COMPONENT_TEXT:
                    data = getTextData($target)
                    break
                case EL_TYPE_HOTSPOT:
                    data = getHotspotData($target)
                    break
                case EL_TYPE_GIF:
                    data = getGifData($target)
                    break
                case EL_TYPE_VIDEO:
                    data = getVideoData($target)
                    break
            }

            return data
        }


        /**
         * 复制组件到另一个组件后面(后面指的是在文档中的位置)
         * @param {JQDOM} $target               要复制的group项
         * @param {JQDOM} $dist_mk              目的地模块
         * @param {Number|String} $left         数值或 'auto', 新复制组件的left值, auto即居中
         * @param {Number|String} $top          数值或 'auto', 新复制组件的top值, auto即居中
         */
        var copy = function ($target, $dist_mk, left, top) {
            
            // 确保以下入参为数值或 'auto'
            left = util.toInt(left, 'auto')
            top = util.toInt(top, 'auto')

            // 获取要复制的组件数据
            var data = getData($target)

            // 生成一个新的
            var $el = renderGroupItem(data)
            $dist_mk.append($el)

            // 设置定位
            var el_pos = util.getCenterPos({
                parent_width: $dist_mk.width(),
                parent_height: $dist_mk.height(),
                target_width: $el.width(),
                target_height: $el.height(),
                left: left,
                top: top,
            })
            $el.css({
                top: el_pos.top,
                left: el_pos.left
            })

            
            // 让新复制的东西显示在最上层
            var max_index = mkMethods.getMaxIndex($dist_mk) + 1
            $el.css('z-index', max_index)

            // 聚焦新复制的东西
            util.nextTick(function () { $el.mousedown() })

            // 确保热区在最上层
            hotspotMethods.allToTop($dist_mk)
        }


        /**
         * 复制组件到另一个组件后面(后面指的是在文档中的位置)
         * @param {JQDOM} $target               要复制的group项
         * @param {JQDOM} $dist_mk              目的地模块
         * @param {Number|String} $left         数值或 'auto', 新复制组件的left值, auto即居中
         * @param {Number|String} $top          数值或 'auto', 新复制组件的top值, auto即居中
         */
        var cut = function ($target, $dist_mk, left, top) {

            // 复制组件
            copy($target, $dist_mk, left, top)

            // 删除原组件
            $target.remove()
        }
        /* --- */
        /* --- */
        /* --- 公用方法 end --- */


        /**
         * 图片组件相关方法
         * @method add($mk, img_url, width, height, left, top)
         * @method getLink($component)
         * @method setLink($component, url)
         * @method adapt($component)
         * @method openTrim($component)
         * @method closeTrim()
         */
        var picMethods = (function () {

            var $triming_el = $()

            /**
             * 增加图片组件
             * @param {JQDOM} $mk       图片所在模块
             * @param {String} img_url  图片链接
             * @param {mix} width       图片宽, 数值或'auto'
             * @param {mix} height      图片高, 数值或'auto'
             * 
             * @return {Promise} promise 返回promise, 图片组件加入成功返回resolve(true), 图片载入失败resolve(false)
             */
            var add = function ($mk, img_url, width, height, left, top) {

                // 确保以下入参为数值或 'auto'
                width   = util.toInt(width, 'auto')
                height  = util.toInt(height, 'auto')
                left    = util.toInt(left, 'auto')
                top     = util.toInt(top, 'auto')

                var img = new Image()
                var promise = new Promise(function (resolve) {

                    // 图片加载完成
                    img.onload = function () {

                        var img_width = img.width
                        var img_height = img.height
                        var cur_width = 0
                        var cur_height = 0
                        var scale = 1

                        if (width == 'auto' && height != 'auto') {
                            // 宽自适应, 高固定
                            
                            scale = height / img_height
                            cur_width = parseInt(img_width * scale)
                            cur_height = height
                        } else if (width != 'auto' && height == 'auto') {
                            // 宽固定, 高自适应

                            scale = width / img_width
                            cur_width = width
                            cur_height = parseInt(img_height * scale)
                        } else if (width != 'auto' && height != 'auto') {
                            // 宽高固定

                            cur_width = width
                            cur_height = height
                        } else {
                            // 宽高随图片宽高

                            cur_width = img_width
                            cur_height = img_height
                        }
                        

                        // 生成图片默认数据
                        var pic_data = groupPicData()
                        // 计算图片位置
                        var component_pos = util.getCenterPos({
                            parent_width: $mk.width(),
                            parent_height: $mk.height(),
                            target_width: cur_width,
                            target_height: cur_height,
                            left: left,
                            top: top,
                        })
                        // 获取当前最大的z-index, 同z-index情况, 文档靠后项 > 文档靠前项
                        var z_index = mkMethods.getMaxIndex($mk)

                        // 盒子信息
                        pic_data.boxStyle = Object.assign(pic_data.boxStyle, {

                            width: cur_width + 'px',
                            height: cur_height + 'px',
                            left: component_pos.left,
                            top: component_pos.top,
                            'z-index': z_index,
                        })
                        // 图片信息
                        pic_data.picStyle = Object.assign(pic_data.picStyle, {

                            width: cur_width + 'px',
                            height: cur_height + 'px',
                            scaling_width: cur_width,
                            scaling_height: cur_height,
                            src: img_url,
                        })


                        // 渲染组件
                        var $el = renderGroupItem(pic_data)
                        // 追加进文档
                        $mk.append($el)
                        // 让组件聚焦, 速度过快, 无法正常定位
                        util.nextTick(function () { $el.mousedown() })
                        // 使热区置顶
                        hotspotMethods.allToTop($mk)


                        resolve($el)
                    }

                    // 图片加载失败
                    img.onerror = function () {

                        resolve(false)
                    }
                })
                img.setAttribute('src', img_url)

                return promise
            }


            /**
             * 获取图片链接
             * @param {JQDOM} $component 
             * @return {String} url 图片链接
             */
            var getLink = function ($component) {

                return $component.find('img').attr('src')
            }


            /**
             * 替换图片
             * @param {JQDOM} $component 图片组件
             * @param {String} url 新图片链接
             */
            var setLink = function ($component, url) {
                
                var img = new Image()
                var promise = new Promise(function (resolve) {

                    img.onload = function () {

                        var $img = $component.find('img')
                        $img.attr('src', url)

                        resolve(true)
                    }

                    img.onerror = function () {
                        resolve(false)
                    }
                })
                img.setAttribute('src', url)

                return promise
            }


            /**
             * 自适应图片, 使图片尽可能多地显示在组件div内, 并上下左右居中显示
             * @param {JQDOM} $component  组件
             */
            var adapt = function ($component) {

                var $img = $component.find('img')

                // 原始宽高
                var origin_width = $img[0].naturalWidth
                var origin_height = $img[0].naturalHeight

                // 组件宽高
                var component_width = $component.width()
                var component_height = $component.height()

                var width_now = 0
                var height_now = 0

                // 优先按短边计算
                if (component_width > component_height) {

                    // 按高算的值
                    var h_height = component_height
                    var h_width = (h_height / origin_height) * origin_width

                    width_now = h_width
                    height_now = h_height
                } else {

                    // 按宽算的值
                    var w_width = component_width
                    var w_height = (w_width / origin_width) * origin_height

                    width_now = w_width
                    height_now = w_height
                }


                // 上下左右居中定位
                var left_now = (component_width - width_now) / 2
                var top_now = (component_height - height_now) / 2


                $img.css({
                    width: width_now,
                    height: height_now,
                    left: left_now,
                    top: top_now,
                })
            }


            /**
             * 开启裁剪状态
             * 此方法更多解释见README.md文档
             * @param {JQDOM} $component 图片组件
             * @returns 无
             */
            var openTrim = function ($component) {

                // 非图片组件跳过
                if (!util.isPicComponent($component)) return

                var $img = $component.find('img')
                var $mk = $component.parents('[el-type="' + EL_TYPE_MK + '"]')

                // 组件状态设为正在裁剪
                $component.data('is_triming', true)

                // 禁止组件移动, 允许里面的图片移动
                $component.data('drag').disable()
                $img.data('drag').enable()

                // 把组件边框线给里面的图片
                componentBoxLine.show($img, getImgOffsetWhenTrim($component))
                
                // 给组件裁剪边框线
                trimBoxLine.show($component, $mk.position())

                // 记录该DOM
                $triming_el = $component
            }


            /**
             * 关闭裁剪状态
             * 此方法更多解释见README.md文档
             */
            var closeTrim = function () {

                // 非图片组件跳过
                if (!util.isPicComponent($triming_el)) return

                // 如果之前没有裁剪状态的DOM则不执行后续逻辑
                if ($triming_el.length == 0) return

                var $img = $triming_el.find('img')

                // 组件状态设为关闭裁剪
                $triming_el.data('is_triming', false)

                // 禁止图片移动, 允许组件移动
                $img.data('drag').disable()
                $triming_el.data('drag').enable()

                // 隐藏裁剪边框线
                trimBoxLine.hide()
            }

            
            /**
             * 计算裁剪时, 图片组件里面的图片的偏移量
             * 此方法更多解释见README.md文档
             * @param {JQDOM} $component 图片组件
             * @returns {Object} {left: xxx, top: xxx}
             */
            var getImgOffsetWhenTrim = function ($component) {
    
                if (!util.isPicComponent($component)) return
    
                var $mk = $component.parents('[el-type="' + EL_TYPE_MK + '"]')
                var mk_pos = $mk.position()
                var component_pos = $component.position()
                var offset = {
                    left: mk_pos.left + component_pos.left,
                    top: mk_pos.top + component_pos.top,
                }
    
                return offset
            }
    

            return {
                add: add,
                getLink: getLink,
                setLink: setLink,
                adapt: adapt,
                openTrim: openTrim,
                closeTrim: closeTrim,
                getImgOffsetWhenTrim: getImgOffsetWhenTrim,
            }
        })()


        /**
         * 更改字体样式的相关方法
         * 
         * @method add($mk, text, placeholder, width, height, left, top)  添加文字组件
         * @method toLeft($component)          居左
         * @method toCenter($component)        居中
         * @method toRight($component)         居右
         * @method toggleBold($component)      切换粗体
         * @method toggleItalic($component)    切换斜体
         * @method toggleCase($component, flag)      切换大小写
         * @method setWordSpace($component, val)     设置字间距
         * @method toggleWritingMode($component)     切换字体方向
         */
        var textMethods = (function () {

            /**
             * 增加文字组件
             * @param {JQDOM} $mk       组件所在模块
             * @param {String} text     内容
             * @param {String} placeholder  占位内容
             * @param {Number} width
             * @param {Number} height
             * @param {Number} left
             * @param {Number} top
             * 
             * @returns void
             */
            var add = function ($mk, text, placeholder, width, height, left, top) {

                // 宽默认200,　高默认40
                width = isNaN(width) ? 200 : parseInt(width)
                height = isNaN(height) ? 40 : parseInt(height)

                // 确保以下入参为数值或 'auto'
                left = util.toInt(left, 'auto')
                top = util.toInt(top, 'auto')

                // 确保text是个字符串
                text = text || ''
                text = $.trim(text.toString())
                placeholder = placeholder || ''
                placeholder = $.trim(placeholder.toString())


                // 默认的数据
                var text_data = groupTextData()
                // 定位
                var component_pos = util.getCenterPos({
                    parent_width: $mk.width(),
                    parent_height: $mk.height(),
                    target_width: width,
                    target_height: height,
                    left: left,
                    top: top,
                })
                var z_index = mkMethods.getMaxIndex($mk)

                // 盒子信息
                text_data.boxStyle = Object.assign(text_data.boxStyle, {

                    width: width + 'px',
                    height: height + 'px',
                    left: component_pos.left,
                    top: component_pos.top,
                    'z-index': z_index,
                })
                // 文字信息
                text_data.textStyle = Object.assign(text_data.textStyle, {
                    
                    value: text
                })

                
                // 渲染组件
                var $el = renderGroupItem(text_data, placeholder)
                // 追加进文档
                $mk.append($el)
                // 让组件聚焦, 速度过快, 无法正常定位
                util.nextTick(function () { $el.mousedown() })
                // 使热区置顶
                hotspotMethods.allToTop($mk)

                // 返回该组件
                return $el
            }


            // 左对齐
            var toLeft = function ($component) {

                var $div = $component.find('div')
                $div.css('text-align', 'left')
            }

            // 居中
            var toCenter = function ($component) {

                var $div = $component.find('div')
                $div.css('text-align', 'center')
            }

            // 右对齐
            var toRight = function ($component) {

                var $div = $component.find('div')
                $div.css('text-align', 'right')
            }

            // 切换加粗
            var toggleBold = function ($component) {

                var $div = $component.find('div')
                var weight = $div.css('font-weight')

                weight = weight == '700' ? '400' : '700'

                switch (weight) {
                    case '400':
                        weight = 'normal'
                        break
                    case '700':
                        weight = 'bold'
                        break
                }

                $div.css('font-weight', weight)
            }

            // 切换斜体
            var toggleItalic = function ($component) {

                var $div = $component.find('div')
                var style = $div.css('font-style')
                style = style == 'italic' ? 'normal' : 'italic'

                $div.css('font-style', style)
            }

            // 切换大小写
            var toggleCase = function ($component, flag) {

                var $div = $component.find('div')
                var html = $div.html()

                if (flag) {

                    html = html.toUpperCase()
                } else {
                    
                    html = html.toLowerCase()
                }

                $div.html(html)
            }

            // 字间距, val是要设置的值, 不能转换为数值或小于0都不会设置
            var setWordSpace = function ($component, val) {

                val = parseInt(val)
                if (isNaN(val) || val < 0) return

                var $div = $component.find('div')
                $div.css('letter-spacing', val + 'px')
            }

            // 切换字体方向
            var toggleWritingMode = function ($component) {

                var $div = $component.find('div')
                writing_mode = $div.css('writing-mode')
                writing_mode = writing_mode == 'horizontal-tb' ? 'vertical-rl' : 'horizontal-tb'

                $div.css('writing-mode', writing_mode)
            }


            return {
                add: add,
                toLeft: toLeft,
                toCenter: toCenter,
                toRight: toRight,
                toggleBold: toggleBold,
                toggleItalic: toggleItalic,
                toggleCase: toggleCase,
                setWordSpace: setWordSpace,
                toggleWritingMode: toggleWritingMode,
            }
        })()


        /**
         * 热区相关方法
         * @method allToTop($mk)      让模块下所有热区置顶
         * @method getLink($hotspot)  获取热区链接
         * @method setLink($hotspot)  设置热区链接
         * @method add($mk, link, width, height, left, top)  添加热区
         */
        var hotspotMethods = (function () {

            // 让模块下所有热区置顶
            var allToTop = function ($mk) {
    
                var index = mkMethods.getMaxIndex($mk) + 1
                var $hotspots = $mk.find('[el-type="'+ EL_TYPE_HOTSPOT +'"]')
                $hotspots.css('z-index', index)
            }
    
    
            // 获取热区的链接
            var getLink = function ($hotspot) {
    
                return $hotspot.find('div').data('link')
            }
    
    
            // 设置热区的链接
            var setLink = function ($hotspot, link) {
                
                $hotspot.find('div').data('link', link)
            }
    
    
            // 添加热区
            var add = function ($mk, link, width, height, left, top) {
    
                // 确保是数值, 默认为150 x 150 的矩形
                width = isNaN(parseInt(width)) ? 150 : parseInt(width)
                height = isNaN(parseInt(height)) ? 150 : parseInt(height)
    
                // 确保是数值
                left = util.toInt(left, 'auto')
                top = util.toInt(top, 'auto')
    
                
                // 生成热区默认数据
                var hotspot_data = groupHotspotData()
                // 计算热区位置
                var pos = util.getCenterPos({
                    parent_width: $mk.width(),
                    parent_height: $mk.height(),
                    target_width: width,
                    target_height: height,
                    left: left,
                    top: top,
                })
                
                // 盒子信息
                hotspot_data.boxStyle = Object.assign(hotspot_data, {

                    width: width + 'px',
                    height: height + 'px',
                    left: pos.left + 'px',
                    top: pos.top + 'px',
                })
                // 热区信息
                hotspot_data.hotspotStyle = Object.assign(hotspot_data.hotspotStyle, {
                    link: link,
                })


                // 渲染热区
                var $el = renderGroupItem(hotspot_data)
                // 追加进文档
                $mk.append($el)
                // 让组件聚焦, 速度过快, 无法正常定位
                util.nextTick(function () { $el.mousedown() })
                // 使热区置顶
                allToTop($mk)

                return $el
            }
    
    
            return {
                allToTop: allToTop,
                getLink: getLink,
                setLink: setLink,
                add: add,
            }
        })()


        // 动图相关方法
        var gifMethods = (function () {

            // 获取动图链接
            var getLink = function ($gif) {

                return $gif.find('img').attr('src')
            }


            /**
             * 设置动图链接, 新的动图宽度为模块宽度, 高根据宽的比例来缩放
             * @param {JQDOM} $gif 
             * @param {String} link 动图链接
             * @returns {Promise}  resolve(true) 或 resolve(false)
             */
            var setLink = function ($gif, link) {
                return (new Promise(function (resolve) {

                    var $mk = $gif.parents('[el-type="'+ EL_TYPE_MK +'"]').eq(0)

                    var img = new Image()
                    img.onload = function () {

                        var naturalWidth = img.naturalWidth
                        var naturalHeight = img.naturalHeight

                        var mk_width = $mk.width()
                        var img_width = mk_width
                        var img_height = (mk_width / naturalWidth) * naturalHeight

                        // 设置动图group项宽高
                        $gif.css({
                            width: img_width,
                            height: img_height,
                        })
                        $gif.find('img').attr('src', link)

                        // 设置编辑器高度
                        mkMethods.setHeight($mk, img_height)
                        // 更新模块边框线高度
                        mkBoxLine.show($mk)
                        // 聚焦模块
                        $mk.mousedown()

                        resolve(true)
                    }
                    img.onerror = function () {

                        resolve(false)
                    }
                    img.setAttribute('src', link)
                }))
            }


            /**
             * 添加动图
             * @param {JQDOM} $mk 目的地模块
             * @param {String} link 动图链接
             * @param {Number} width 动图宽度
             * @param {Number} height 动图高度
             * @returns 
             */
            var add = function ($mk, link, width, height) {

                // 确保宽高为数值或'auto'
                width = util.toInt(width, 'auto')
                height = util.toInt(height, 'auto')

                var img = new Image()
                var promise = new Promise(function (resolve) {

                    // 动图加载完成
                    img.onload = function () {

                        var naturalWidth = img.naturalWidth
                        var naturalHeight = img.naturalHeight
                        var cur_width = 0
                        var cur_height = 0
                        var scale = 1

                        if (width == 'auto' && height != 'auto') {
                            // 宽自适应, 高固定
                            
                            scale = height / naturalWidth
                            cur_width = parseInt(naturalHeight * scale)
                            cur_height = height
                        } else if (width != 'auto' && height == 'auto') {
                            // 宽固定, 高自适应

                            scale = width / naturalHeight
                            cur_width = width
                            cur_height = parseInt(naturalWidth * scale)
                        } else if (width != 'auto' && height != 'auto') {
                            // 宽高固定

                            cur_width = width
                            cur_height = height
                        } else {
                            // 宽高随图片宽高

                            cur_width = naturalWidth
                            cur_height = naturalHeight
                        }


                        // 生成动图默认数据
                        var gif_data = groupGifData()
                        // 盒子信息
                        gif_data.boxStyle = Object.assign(gif_data.boxStyle, {
                            width: cur_width + 'px',
                            height: cur_height + 'px',
                            left: 0,
                            top: 0,
                            'z-index': 0,
                        })
                        // 图片信息
                        gif_data.gifStyle = Object.assign(gif_data.gifStyle, {
                            src: link
                        })


                        // 渲染组件
                        var $el = renderGroupItem(gif_data)
                        // 设置模块高度跟视频高度一致
                        mkMethods.setHeight($mk, cur_height)
                        // 使模块聚焦
                        $mk.mousedown()
                        // 追加进文档
                        $mk.append($el)

                        resolve($el)
                    }

                    img.onerror = function () {

                        resolve(false)
                    }
                })
                img.setAttribute('src', link)

                return promise
            }


            return {
                getLink: getLink,
                setLink: setLink,
                add: add,
            }
        })()


        // 视频相关方法
        var videoMethods = (function () {

            // 播放视频
            var play = function ($video) {

                var video = $video.find('video')[0]
                video.play()
            }


            // 暂停播放
            var pause = function ($video) {

                var video = $video.find('video')[0]
                video.pause()
            }


            // 静音
            var mute = function ($video) {

                var video = $video.find('video')[0]
                video.muted = true
            }


            // 开启声音
            var phonic = function ($video) {

                var video = $video.find('video')[0]
                video.muted = false
            }


            // 获取视频链接
            var getLink = function ($video) {
                
                return $video.find('video').attr('src')
            }


            /**
             * 设置视频链接, 新的视频宽度为模块的宽度, 高根据宽的比例来缩放
             * @param {JQDOM} $video 
             * @param {String} link 
             * @returns {Promise}  resolve(true) 或 resolve(false)
             */
            var setLink = function ($video, link) {
                return (new Promise(function (resolve) {

                    var $mk = $video.parents('[el-type="'+ EL_TYPE_MK +'"]').eq(0)

                    var $tmp_video = $('<video></video>')
                    $tmp_video.on('canplay', function () {

                        var naturalWidth = $tmp_video[0].videoWidth
                        var naturalHeight = $tmp_video[0].videoHeight

                        var mk_width = $mk.width()
                        var video_width = mk_width
                        var video_height = (mk_width / naturalWidth) * naturalHeight

                        // 设置视频gruop项宽高
                        $video.css({
                            width: video_width,
                            height: video_height,
                        })
                        $video.find('video').attr('src', link)

                        // 设置编辑器高度
                        mkMethods.setHeight($mk, video_height)
                        // 更新模块边框线高度
                        mkBoxLine.show($mk)
                        // 聚焦模块
                        $mk.mousedown()

                        resolve(true)
                    })
                    $tmp_video.on('error', function () {
                        
                        resolve(false)
                    })
                    $tmp_video.attr('src', link)
                }))
            }


            /**
             * 添加视频, 如果没有视频
             * @param {JQDOM} $mk 目的地模块
             * @param {String} link 视频链接
             * @param {Number} width 视频宽度
             * @param {Number} height 视频高度
             * @returns {Promise}
             */
            var add = function ($mk, link, width, height) {

                // 确保宽高为数值或'auto'
                width = util.toInt(width, 'auto')
                height = util.toInt(height, 'auto')

                var $tmp_video = $('<video></video>')
                var promise =  new Promise(function (resolve) {

                    // 视频加载完成
                    $tmp_video.on('canplay', function () {

                        var naturalWidth = $tmp_video[0].videoWidth
                        var naturalHeight = $tmp_video[0].videoHeight
                        var cur_width = 0
                        var cur_height = 0
                        var scale = 1

                        if (width == 'auto' && height != 'auto') {
                            // 宽自适应, 高固定
                            
                            scale = height / naturalWidth
                            cur_width = parseInt(naturalHeight * scale)
                            cur_height = height
                        } else if (width != 'auto' && height == 'auto') {
                            // 宽固定, 高自适应

                            scale = width / naturalHeight
                            cur_width = width
                            cur_height = parseInt(naturalWidth * scale)
                        } else if (width != 'auto' && height != 'auto') {
                            // 宽高固定

                            cur_width = width
                            cur_height = height
                        } else {
                            // 宽高随图片宽高

                            cur_width = naturalWidth
                            cur_height = naturalHeight
                        }


                        // 生成视频默认数据
                        var video_data = groupVideoData()
                        // 盒子信息
                        video_data.boxStyle = Object.assign(video_data.boxStyle, {
                            width: cur_width + 'px',
                            height: cur_height + 'px',
                            left: 0,
                            top: 0,
                            'z-index': 0,
                        })
                        // 图片信息
                        video_data.videoStyle = Object.assign(video_data.videoStyle, {
                            src: link
                        })


                        // 渲染组件
                        var $el = renderGroupItem(video_data)
                        // 设置模块高度跟视频高度一致
                        mkMethods.setHeight($mk, cur_height)
                        // 使模块聚焦
                        $mk.mousedown()
                        // 追加进文档
                        $mk.append($el)

                        resolve($el)
                    })

                    $tmp_video.on('error', function () {

                        resolve(false)
                    })
                })
                $tmp_video.attr('src', link)

                return promise
            }

            
            return {
                play: play,
                pause: pause,
                mute: mute,
                phonic: phonic,
                add: add,
                setLink: setLink,
                getLink: getLink,
            }
        })()


        return  {
            centerGroupItem: centerGroupItem,
            goUp: goUp,
            goDown: goDown,
            getData: getData,
            copy: copy,
            cut: cut,
            del: del,
            textMethods: textMethods,
            picMethods: picMethods,
            hotspotMethods: hotspotMethods,
            gifMethods: gifMethods,
            videoMethods: videoMethods,
        }

    })()


    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /* -------------------- 方法 end -------------------- */


    /* -------------------- 渲染 -------------------- */
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    // 渲染groups数组
    var renderGroupItem = (function () {
    
        // 渲染组件的图片
        var renderComponentPic = function (picStyle) {
    
            var $img = $('<img>')
            $img.css(picStyle)
            $img.attr('src', picStyle.src)
            $img.data('scaling_width', picStyle.scaling_width)
            $img.data('scaling_height', picStyle.scaling_height)
    
            return $img
        }
    
    
        // 渲染组件的文字
        var renderComponentText = function (textStyle, placeholder) {
    
            var $div = $('<div></div>')
            $div.attr('wrap', 'hard')
            $div.attr('spellcheck', 'false')
            $div.attr('contenteditable', 'plaintext-only')
            $div.attr('placeholder', placeholder)
            $div.css(textStyle)
            $div.html(textStyle.value)
    
            return $div
        }


        // 渲染热区
        var renderHotspot = function (hotspotStyle) {

            var $div = $('<div></div>')
            $div.css(hotspotStyle)
            $div.data('link', hotspotStyle.link)
    
            return $div
        }


        // 渲染动图
        var renderGif = function (gifStyle) {

            var $img = $('<img>')
            $img.css(gifStyle)
            $img.attr('src', gifStyle.src)

            return $img
        }


        // 渲染视频
        var renderVideo = function (videoStyle) {

            // 默认静音、循环播放
            var $video = $('<video muted loop></video>')
            $video.css(videoStyle)
            $video.attr('src', videoStyle.src)

            return $video
        }


        return function (group_item, placeholder) {

            var type         = group_item.type
            var boxStyle     = group_item.boxStyle
            var picStyle     = group_item.picStyle     || {}
            var textStyle    = group_item.textStyle    || {}
            var hotspotStyle = group_item.hotspotStyle || {}
            var gifStyle     = group_item.gifStyle     || {}
            var videoStyle   = group_item.videoStyle   || {}
            
            boxStyle     = Object.assign({}, group_default_style, boxStyle)
            picStyle     = Object.assign({}, group_pic_default_style, picStyle)
            textStyle    = Object.assign({}, group_text_default_style, textStyle)
            hotspotStyle = Object.assign({}, group_hotspot_default_style, hotspotStyle)
            gifStyle     = Object.assign({}, group_gif_default_style, gifStyle)
            videoStyle   = Object.assign({}, group_video_default_style, videoStyle)

            // placeholder 文字组件时有用
            placeholder = placeholder || ''
            placeholder = $.trim(placeholder.toString())

            // 生成外部div
            var $wrap = $('<div></div>').css(boxStyle)

            // 生成内容DOM
            var $contentEl = null
            switch (type) {

                // 内容为图片时
                case COMPONENT_TYPE_PIC:

                    $wrap.attr('el-type', EL_TYPE_COMPONENT_PIC)
                    $contentEl = renderComponentPic(picStyle)
                    break

                // 内容为文字时
                case COMPONENT_TYPE_TEXT:
                    
                    $wrap.attr('el-type', EL_TYPE_COMPONENT_TEXT)
                    $contentEl = renderComponentText(textStyle, placeholder)
                    break

                // 内容为热区时
                case COMPONENT_TYPE_HOTSPOT:
                    
                    $wrap.attr('el-type', EL_TYPE_HOTSPOT)
                    $contentEl = renderHotspot(hotspotStyle)
                    break

                // 内容为动图时
                case COMPONENT_TYPE_GIF:
                    
                    $wrap.attr('el-type', EL_TYPE_GIF)
                    $contentEl = renderGif(gifStyle)
                    break

                // 内容为视频时
                case COMPONENT_TYPE_VIDEO:
                    
                    $wrap.attr('el-type', EL_TYPE_VIDEO)
                    $contentEl = renderVideo(videoStyle)
                    break
            }
            
            // 把内容加入壳子里
            $wrap.append($contentEl)

            // 保存数据源
            $wrap.data('source', group_item)

            // 绑定事件, 延迟绑定, 等当前这个组件渲染完成
            util.nextTick(function () { groupEvent($wrap) })

            return $wrap
        }
    })()


    // 渲染单个模块
    var renderMk = function (source_item) {

        // 生成模块外层div
        var boxStyle = Object.assign({}, mk_default_style, source_item.boxStyle)
        var $mk = $('<div el-type="' + EL_TYPE_MK + '"></div>').css(boxStyle)


        // 渲染groups
        var groups = source_item.groups
        for (var i = 0, len = groups.length; i < len; i++) {

            // 渲染groups里的每一项
            var $item = renderGroupItem(groups[i])
            $mk.append($item)
        }
        // 添加事件
        mkEvent($mk)
        // 保存数据源
        $mk.data('source', source_item)
        // 去掉组件的index空挡
        mkMethods.trimIndex($mk)

        // 使模块内所有热区置顶
        groupMethods.hotspotMethods.allToTop($mk)

        return $mk
    }


    // 渲染整个编辑器
    var render = function (source) {

        var $mk_arr = []

        // 渲染模块
        for (var i = 0, len = source.length; i < len; i++) {

            var $mk = renderMk(source[i])

            $mk_arr.push($mk)
            $entity.append($mk)
        }

        // 绑定事件
        util.nextTick(function () { editorEvent() })
    }
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /* -------------------- 渲染 end -------------------- */


    /* -------------------- 初始化 -------------------- */
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    ;(function (source) {

        // 渲染
        render(source)

        
        // 点击编辑器以外的地方的时候
        util.onDocumentClick($editor, null, function () {
    
            // 隐藏模块边框线
            mkBoxLine.hide()
            // 隐藏组件边框线
            componentBoxLine.hide()
            // 隐藏热区边框线
            hotspotBoxLine.hide()
            // 取消可能存在的组件裁剪状态
            groupMethods.picMethods.closeTrim()
        })
    })(source)
    /* --- */
    /* --- */
    /* --- */
    /* --- */
    /* -------------------- 初始化 end -------------------- */


    return {
        // 编辑器相关方法
        editorMethods: editorMethods,
        // 模块相关方法
        mkMethods: mkMethods,
        // group项相关方法
        groupMethods: groupMethods,
        // 辅助线相关方法
        guideLine: guideLine,
        // 事件订阅器
        on: util.observer.on,
        // 用于生成图片
        generateImg: generateImg,
    }
}