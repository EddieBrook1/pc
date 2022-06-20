;(function () {

    // 初始化拖动效果, 返回一个方法用于清除拖动效果
    var initDivider = function () {

        var $divider = $('[js-hook="divider"]')
        var $divider_btn = $divider.find('.btn')

        var $img_wrap = $('[js-hook="img-wrap"]')
        var $before = $img_wrap.find('[js-hook="before"]')

        // 显示分割线
        $divider.show()
        // 初始化分割线的位置, 设置在中间
        $divider.css('left', '50%')
        // 设置抠图前图片width, 等于$divider的left值
        $before.css('width', $divider.position().left + 'px')

        // 拖动事件
        var min_left = $divider_btn.width() / 2
        var max_left = $img_wrap.width() - ($divider_btn.width() / 2)
        var st_x = 0
        var st_left = 0
        var move_fn = function (e) {

            var cur_x = e.clientX
            var diff_x = cur_x - st_x
            var left = st_left + diff_x

            left = left <= min_left ? min_left : left
            left = left >= max_left ? max_left : left

            left = left + 'px'
            $divider.css('left', left)

            $before.css('width', $divider.position().left + 'px')
        }
        var down_fn = function (e) {

            st_x = e.clientX
            st_left = $divider.position().left

            // 绑定事件
            $(document).on('mousemove', move_fn)

            // 移除事件
            $(document).on('mouseup', function () {
                $(document).off('mousemove', move_fn)
            })
        }

        // 清除原来的事件
        $divider_btn.off('mousedown', down_fn)
        $(document).off('mousemove', move_fn)

        // 重新绑定事件
        $divider_btn.on('mousedown', down_fn)


        return function (bool) {
            is_clear = bool
        }
    }


    // 初始化图片
    var initImg = function (before_img, after_img, callback) {

        var $img_wrap = $('[js-hook="img-wrap"]')
        var $before_img = $img_wrap.find('[js-hook="before"]').find('img')
        var $after_img = $img_wrap.find('[js-hook="after"]').find('img')
        
        var max_width = $img_wrap.width()
        var max_height = $img_wrap.height()

        // 先隐藏避免闪烁
        $before_img.hide()
        $after_img.hide()

        // 取消图片标签的宽高
        $before_img.css({
            width: 'unset',
            height: 'unset',
        })
        $after_img.css({
            width: 'unset',
            height: 'unset',
        })

        // 原图加载完成
        var before_ok = false
        $before_img.one('load', function () { before_ok = true })

        // 抠图后加载成功
        var after_ok = false
        $before_img.one('load', function () { after_ok = true })

        // 设置图片
        $before_img.attr('src', before_img)
        $after_img.attr('src', after_img)

        // 直到两张图片加载完成再执行相关逻辑
        var timer = setInterval(function () {

            if (!before_ok || !after_ok) return
            clearInterval(timer)
            timer = null

            $before_img.show()
            $after_img.show()

            var img_width = $before_img.width()
            var img_height = $before_img.height()

            // 图片过高则按高的比例缩小
            if (img_height > max_height) {
                
                $before_img.css({
                    width: 'unset',
                    height: max_height
                })
                
                $after_img.css({
                    width: 'unset',
                    height: max_height
                })
            } else {

                // 限制宽在最大宽内
                img_width = img_width > max_width ? max_width : img_width
                
                console.log(img_width);
                $before_img.css({
                    width: img_width,
                    height: 'unset'
                })
                
                $after_img.css({
                    width: img_width,
                    height: 'unset'
                })
            }


            ;(callback instanceof Function) && callback()
        }, 100)
    }


    // 错误提示语
    var tipPop = (function () {

        var timer = null
        var $tip_pop = $('[js-hook="tip-pop"]')
        var type_list = ['success', 'info', 'warning', 'danger']


        var open = function (msg, type, timeout) {

            type = type_list.indexOf(type) == -1
                ? 'info'
                : type

            timeout = isNaN(timeout)
                ? 2000
                : timeout

            clearTimeout(timer)
            $tip_pop.html(msg)

            // 清除掉其他修饰类
            for (var i = 0, len = type_list.length; i < len; i++) {
                $tip_pop.removeClass(type_list[i])
            }
            
            // 当前修饰类
            $tip_pop.addClass(type)

            // 显示提示语
            $tip_pop.addClass('active')
            
            timer = setTimeout(function () {

                clearTimeout(timer)
                timer = null
                $tip_pop.removeClass('active')
            }, timeout)
        }


        var close = function () {

            clearTimeout(timer)
            $tip_pop.removeClass('active')
        }

        return {
            open: open,
            close: close,
        }
    })()
    

    // 初始化底部
    var initFooter = function () {

        var $file_input = $('[js-hook="file-input"]')
        var $upload = $('[js-hook="upload"]')
        var $download = $('[js-hook="download"]')
        var is_uploading = false

        console.log($download);
        // 实例化上传插件, https://docs.fineuploader.com/branch/master/api/events.html
        var uploader = new qq.FineUploaderBasic({
            autoUpload: true,
            maxConnections: 3,
            request: {
                endpoint: 'http://test.qianzhiyu.net/koutu/upload',
                methods: 'POST',
            },
            chunking: {
                enabled: true,
                mandatory: true,
                partSize: 1024 * 1024 * 5
            },

            callbacks: {

                // 上传前
                onUpload (id, name) {

                    $upload.removeClass('default')
                    $upload.addClass('uploading')
                    is_uploading = true
                    tipPop.open('正在上传, 请稍后')
                },
                
                // 上传完成
                onComplete (id, name, res, xhr) {

                    $upload.removeClass('uploading')
                    $upload.addClass('default')

                    if (res.code == -1) {
                        
                        tipPop.open(res.msg, 'danger')
                    } else {

                        initImg(res.data.origin_url, res.data.after_url, function () {

                            is_uploading = false
                            initDivider()
                            $download.attr('href', res.data.download_url)
                            tipPop.close()
                        })
                    }
                },

                // 全部完成
                onAllComplete (succ_arr, fail_arr) {},

                // 出错
                onError (id, name, reason, xhr) {},

                // 过程
                onProgress (id, name, upload_bytes, total_bytes) {},

                // 全部过程
                onTotalProgress (total_upload_bytes, total_bytes) {},

                // 分片上传前
                onUploadChunk (id, name, chunk_data) {},


                // 状态改变
                onStatusChange (id, old_status, new_status) {},
            },
        })

        // 上传图片, 转发点击事件
        $upload.click(function () {

            if (is_uploading) return
            $file_input.click()
        })

        // 侦听文件变换
        $file_input.on('change', function () {
            
            if (is_uploading) return
            uploader.addFiles(this.files)
        })

        // “结果下载”点击事件, 如果没有下载链接则不下载
        $download.click(function (e) {

            if ($(this).attr('href') == '') {

                e.preventDefault()
                return
            }
        })
    }


    // 初始化
    ;(function () {

        $.get('http://test.qianzhiyu.net/koutu/example_list').then(function (res) {

            if (res.code == -1) {

                tipPop.open('初始化失败, 请稍后重试', 'danger')
                return
            }

            // 加载示例图
            initImg(res.data.origin_url, res.data.after_url, function () {

                initDivider()
            })

            // 初始化底部按钮
            initFooter()
        })
    })()
})()