;(function () {

    // 编辑器实体内容区
    var $entity = $('#editor .entity')

    // 当前商品id
    var goods_id = 584181130977
    // 当前模版id
    var template_id = 101203

    /**
     * 把base64转成Blob类型
     * @param {String} base64
     * @param {String} filename
     * 
     * @returns {Blob} 类似文件的一个对象, 可用于上传
     */
    var base64ToFile = (function () {

        // 转blob类型, 返回promise
        var base64ToBlob = function(base64Data) {

            var arr = base64Data.split(',')
            var fileType = arr[0].match(/:(.*?);/)[1]
            var bstr = atob(arr[1])
            var l = bstr.length
            var u8Arr = new Uint8Array(l)

            return new Promise(function (resolve) {

                while (l--) {
                    u8Arr[l] = bstr.charCodeAt(l)

                    // 全部执行完成, 解决promise
                    if (l == 0) {

                        var blob = new Blob([u8Arr], { type: fileType })
                        resolve(blob)
                    }
                }
            })
        }


        // blob转文件类型
        var blobToFile = function (newBlob, filename) {

            newBlob.lastModifiedDate = new Date();
            newBlob.name = filename;
            return newBlob;
        }


        return function (base64, filename) {

            return base64ToBlob(base64).then(function (blob) {

                return blobToFile(blob, filename)
            })
        }
    })()


    /**
     * 生成并下载zip, 该方法会生成一个叫 "千绘设计.zip" 的压缩包, 压缩包结构如下:
        - data.json
        - images
            - 1.png
            - 2.png
            - 3.png
            - .........
     * 
     * @param {String} data_json 模版数据的json格式
     * @param {Array} base64_arr 图片的base64数据数组
     * @param {String} zip_name  压缩包的名称, 不带后缀
     */
    var generateAndDownloadZip = function (data_json, base64_arr, zip_name) {

        zip_name = zip_name.toString()

        var zip = new JSZip()

        // 添加一个文件
        zip.file('data.json', data_json)

        // 添加一个文件夹
        var img = zip.folder("images")


        // 往文件夹里添加图片
        for (var i = 0, len = base64_arr.length; i < len; i++) {

            var base64_now = base64_arr[i]['base64'].split(',')[1]
            img.file(i + 1 + '.png', base64_now, {base64: true})
        }

        // 生成zip文件
        return zip.generateAsync({type: 'blob'}).then(function (content) {

            saveAs(content, "千绘设计.zip")
        })
    }


    // 下载图片, 按模块
    ;(function () {

        var $btn = $('.J_download-pic-mk')  // 按钮
        var $progress = $btn.find('span')   // 进度

        $btn.on('click', function () {

            $progress.html('0%')
            var $mks = $entity.find('[el-type="mk"]')

            // 计算模块高度和偏移
            var pos = []
            for (var i = 0, len = $mks.length; i < len; i++) {

                var limit = $mks.eq(i).height()
                var offset = null
                if (pos.length == 0) {

                    offset = 0
                } else {

                    var last_pos = pos[pos.length - 1]
                    offset = last_pos.offset + last_pos.limit
                }

                pos.push({
                    offset: offset,
                    limit: limit,
                })
            }


            // 执行生成图片命令
            editor.generateImg.exec({

                chunk_pos: pos,

                // 更新进度
                onProgress: function (chunk_offset, total_length, base64_arr) {

                    var progress = Math.floor(chunk_offset / total_length * 100)
                    $progress.html(progress + '%')
                },


                // 全部生成完成
                onComplete: function (base64_arr) {

                    console.log('下载图片-按模块-图片生成完成, 正在调起下载...')

                    var data_json = JSON.stringify(editor.editorMethods.getData())
                    generateAndDownloadZip(data_json, base64_arr, '千绘设计')
                }
            })
        })
    })()


    // 下载图片, 自定义高度
    ;(function () {

        var $btn = $('.J_download-pic-960') // 按钮
        var $progress = $btn.find('span')   // 进度

        $btn.on('click', function () {

            var item_height = prompt('模块高度:', "960")
            if (item_height == null) return

            item_height = parseInt(item_height)
            item_height = isNaN(item_height) ? 960 : item_height
            item_height = item_height <= 0 ? 960 : item_height
            
            $progress.html('0%')
            var editor_height = editor.editorMethods.getHeight()

            var pos = []
            while (editor_height > 0) {
                
                var offset = pos.length == 0
                    ? 0
                    : pos[pos.length - 1].offset + item_height

                    
                var limit = editor_height < item_height
                    ? editor_height
                    : item_height

                pos.push({
                    offset: offset,
                    limit: limit,
                })
                editor_height = editor_height - item_height
            }


            // 执行生成图片命令
            editor.generateImg.exec({

                chunk_pos: pos,

                // 更新进度
                onProgress: function (chunk_offset, total_length, base64_arr) {

                    var progress = Math.floor(chunk_offset / total_length * 100)
                    $progress.html(progress + '%')
                },


                // 全部生成完成
                onComplete: function (base64_arr) {

                    console.log('下载图片-960-图片生成完成, 正在调起下载...')

                    var data_json = JSON.stringify(editor.editorMethods.getData())
                    generateAndDownloadZip(data_json, base64_arr, '千绘设计')
                }
            })
        })
    })()


    
    // 判断是否为原图模块
    var isOriginMk = function ($mk) {

        var $components = $mk.find('[el-type="component-pic"]')
        if ($components.length !== 1) return false

        var $pic = $components.eq(0)
        var $img = $pic.find('img')
        var mk_width = $mk.width()
        var mk_height = $mk.height()

        if (
            ($pic.width() == mk_width && $pic.height() == mk_height) &&
            ($img.width() == mk_width && $img.height() == mk_height)
        ) {

            return true
        } else {

            return false
        }
    }


    // 同步-按模块
    ;(function () {

        var $btn = $('.J_sync-mk')          // 按钮
        var $progress = $btn.find('span')    // 进度\

        $btn.on('click', function () {

            // isOnSync = true
            $progress.html('0%')

            // 当前正在生成的blob的promise
            var blob_promises = []
            // 当前编辑器数据
            var editor_data = editor.editorMethods.getData()

            // 算出模块的位置
            var $all_mks = $entity.find('[el-type="mk"]')
            var mk_pos = [];
            for (var i = 0, len = $all_mks.length; i < len; i++) {

                var limit = $all_mks.eq(i).height()
                var offset = null
                if (mk_pos.length == 0) {

                    offset = 0
                } else {

                    var last_item = mk_pos[mk_pos.length - 1]
                    offset = last_item.offset + last_item.limit
                }

                mk_pos.push({
                    offset: offset,
                    limit: limit,
                })
            }


            /**
             * 计算图片的起始位置(offset)和要截取多少(limit)
             * 图片有2种情况, 第一种是常见的, 即图片是一张新图片正常生成即可
             * 第二种是该图片的 limit和offset 刚好和mk_pos中的某一项匹配, 这时要判断它是否为原图模块并进行额外处理
             * 
             * pics里包含 editor.generateImg.exec 执行所必须的 limit和offset 信息, 同时包含了该图片的宽、高、是否为原图和该位置信息代表第几个分片
             * pics_index会被递增, 它表示pics里的项是第几个分片, 后面上传完成后需要根据这个值来设置对应的id
             */
            var pics = []
            var pics_index = 0
            for (var i = 0, len = $all_mks.length; i < len; i++) {

                var limit = $all_mks.eq(i).height()
                var offset = null
                if (pics.length == 0) {

                    offset = 0
                } else {

                    var last_pics = pics[pics.length - 1]
                    offset = last_pics.offset + last_pics.limit
                }


                // 跟模块高度判断, 看有没有相同的; 如果有判断是否为原图模块, 如果是记录并跳过
                var url_now = null
                var origin_pic_width = null
                var origin_pic_height = null
                for (var j = 0, jlen = mk_pos.length; j < jlen; j++) {

                    var mk_pos_item = mk_pos[j]
                    if (mk_pos_item.limit == limit && mk_pos_item.offset == offset) {

                        var $mk_now = $all_mks.eq(i)

                        // 如果是原图模块提取出它的链接
                        if (isOriginMk($mk_now)) {

                            var $origin_pic = $mk_now.find('[el-type="component-pic"]').eq(0)
                            url_now = editor.groupMethods.picMethods.getLink($origin_pic)
                            origin_pic_width = $origin_pic.width()
                            origin_pic_height = $origin_pic.height()
                        }
                    }
                }

                // 当前图片数据
                var pics_item = {
                    index: pics_index,
                    width: 750,
                    height: limit,
                    offset: offset,
                    limit: limit,
                }
                
                if (url_now == null) {

                    pics_item.type = 'local'
                    pics_item.local_id = null
                    pics_item.url = ''
                } else {
                    
                    pics_item.type = 'origin'
                    pics_item.url = url_now
                    pics_item.width = origin_pic_width
                    pics_item.height = origin_pic_height
                }

                pics.push(pics_item)
                pics_index += 1
            }


            // 直接每次点击时实例化一个新的就好, 可以少很多麻烦, 也不容易出错
            var uploader = new qq.FineUploaderBasic({
                autoUpload: true,
                maxConnections: 3,

                request: {
                    endpoint: 'http://test.qianzhiyu.net/common/upload/file',
                    methods: 'POST',
                },
                chunking: {
                    enabled: true,
                    mandatory: true,
                    partSize: 1024 * 1024 * 5
                },

                // 事件
                callbacks: {
            
                    // 上传前
                    onUpload: function (id, name) {},
                    
                    // 上传完成
                    onComplete: function (id, name, res, xhr) {

                        var file = this.getFile(id)
                        var chunk_index = file.chunk_index
                        var local_id = res.data.id

                        // 找到对应的 pic 项并赋值
                        for (var i = 0, len = pics.length; i < len; i++) {

                            if (pics[i].index == chunk_index) {

                                pics[i].local_id = local_id
                            }
                        }
                    },
            
                    // 全部完成
                    onAllComplete: function (succ_arr, fail_arr) {
                        
                        // 该方法有bug, 可能会调用多次
                        if (succ_arr.length == 0 && fail_arr.length == 0) return

                        // 如果有图片上传失败给出提示
                        if (fail_arr.length > 0) {

                            console.warn('同步失败, 有图片上传失败')
                            return
                        }

                        
                        var post_pics = []
                        for (var i = 0, len = pics.length; i < len; i++) {

                            var pics_item = pics[i]
                            var post_pics_item = {
                                type: pics_item.type,
                                width: pics_item.width,
                                height: pics_item.height,
                                url: pics_item.url,
                            }

                            // 如果是本地图片还要加上本地图片的id
                            if (pics_item.type == 'local') {

                                post_pics_item['local_id'] = pics_item.local_id
                            }
                            post_pics.push(post_pics_item)
                        }

                        // 提交同步请求
                        $.post('http://test.qianzhiyu.net/tb/sync/pc', {

                            goods_id: goods_id,
                            template_id: template_id,
                            editor_data: JSON.stringify(editor_data),
                            pics: JSON.stringify(post_pics),
                        }).then(function (res) {

                            console.log(res);
                        })
                    },
            
                    // 出错
                    onError: function (id, name, reason, xhr) {},
            
                    // 过程
                    onProgress: function (id, name, upload_bytes, total_bytes) {},
            
                    // 全部过程
                    onTotalProgress: function (total_upload_bytes, total_bytes) {},
            
                    // 分片上传前
                    onUploadChunk: function (id, name, chunk_data) {
                        return Promise.resolve({
                            params: { real_filename: this.getFile(id).name }
                        })
                    },

                    // 状态改变
                    onStatusChange: function (id, old_status, new_status) {},
                },
            })


            // 执行生成图片命令
            editor.generateImg.exec({

                // 只生成本地图片项
                chunk_pos: (function () {

                    // 取出本地图片项
                    var local_pics = []
                    for (var i = 0, len = pics.length; i < len; i++) {

                        if (pics[i].type == 'local') {

                            local_pics.push(pics[i])
                        }
                    }

                    return local_pics
                })(),

                // 更新进度
                onProgress: function (chunk_offset, total_length) {

                    var progress = Math.floor(chunk_offset / total_length * 100)
                    $progress.html(progress + '%')
                },


                // 分片完成
                onChunkComplete: function (pos_item, base64_item) {

                    // 生成随机名称, 按顺序存入生成的blob
                    var filename = Date.now().toString() + blob_promises.length + '.png'
                    var blob = base64ToFile(base64_item.base64, filename).then(function (blob) {

                        // 即上面的 pos_index
                        blob.chunk_index = pos_item['index']
                        return blob
                    })

                    var index = base64_item['index']
                    blob_promises[index] = blob
                },


                // 全部生成完成
                onComplete: function (base64_arr) {

                    // 等待所有blob生成完之后就可以开始上传了
                    Promise.all(blob_promises).then(function (blobs) {

                        uploader.addFiles(blobs)
                    })
                }
            })
        })
    })()


    // 同步-自定义
    ;(function () {

        var $btn = $('.J_sync-960')          // 按钮
        var $progress = $btn.find('span')    // 进度\

        $btn.on('click', function () {

            $progress.html('0%')

            // 当前正在生成的blob的promise
            var blob_promises = []         
            // 当前编辑器高度
            var editor_height = editor.editorMethods.getHeight()
            // 当前编辑器数据
            var editor_data = editor.editorMethods.getData()

            // 算出模块的位置
            var $all_mks = $entity.find('[el-type="mk"]')
            var mk_pos = [];
            for (var i = 0, len = $all_mks.length; i < len; i++) {

                var limit = $all_mks.eq(i).height()
                var offset = null
                if (mk_pos.length == 0) {

                    offset = 0
                } else {

                    var last_item = mk_pos[mk_pos.length - 1]
                    offset = last_item.offset + last_item.limit
                }

                mk_pos.push({
                    offset: offset,
                    limit: limit,
                })
            }


            /**
             * 计算图片的起始位置(offset)和要截取多少(limit)
             * 图片有2种情况, 第一种是常见的, 即图片是一张新图片正常生成即可
             * 第二种是该图片的 limit和offset 刚好和mk_pos中的某一项匹配, 这时要判断它是否为原图模块并进行额外处理
             * 
             * pics里包含 editor.generateImg.exec 执行所必须的 limit和offset 信息, 同时包含了该图片的宽、高、是否为原图和该位置信息代表第几个分片
             * pics_index会被递增, 它表示pics里的项是第几个分片, 后面上传完成后需要根据这个值来设置对应的id
             */
            var item_height = parseInt(prompt('输入切割高度', '960'))
            item_height = isNaN(item_height) ? 960 : item_height
            item_height = item_height < 0 ? 960 : item_height

            var pics = []
            var pics_index = 0
            while (editor_height > 0) {
                
                var offset = pics.length == 0
                    ? 0
                    : pics[pics.length - 1].offset + item_height

                    
                var limit = editor_height < item_height
                    ? editor_height
                    : item_height


                // 跟模块高度判断, 看有没有相同的; 如果有判断是否为原图模块, 如果是记录并跳过
                var url_now = null
                var origin_pic_width = null
                var origin_pic_height = null
                for (var i = 0, len = mk_pos.length; i < len; i++) {

                    var mk_pos_item = mk_pos[i]
                    if (mk_pos_item.limit == limit && mk_pos_item.offset == offset) {

                        var $mk_now = $all_mks.eq(i)

                        // 如果是原图模块提取出它的链接
                        if (isOriginMk($mk_now)) {

                            var $origin_pic = $mk_now.find('[el-type="component-pic"]').eq(0)
                            url_now = editor.groupMethods.picMethods.getLink($origin_pic)
                            console.log();
                            origin_pic_width = $origin_pic.width()
                            origin_pic_height = $origin_pic.height()
                        }
                    }
                }

                // 当前图片数据
                var pics_item = {
                    index: pics_index,
                    width: 750,
                    height: limit,
                    offset: offset,
                    limit: limit,
                }
                
                if (url_now == null) {

                    pics_item.type = 'local'
                    pics_item.local_id = null
                    pics_item.url = ''
                } else {
                    
                    pics_item.type = 'origin'
                    pics_item.url = url_now
                    pics_item.width = origin_pic_width
                    pics_item.height = origin_pic_height
                }

                pics.push(pics_item)
                editor_height = editor_height - item_height
                pics_index += 1
            }


            // 直接每次点击时实例化一个新的就好, 可以少很多麻烦, 也不容易出错
            var uploader = new qq.FineUploaderBasic({
                autoUpload: true,
                maxConnections: 3,

                request: {
                    endpoint: 'http://test.qianzhiyu.net/common/upload/file',
                    methods: 'POST',
                },
                chunking: {
                    enabled: true,
                    mandatory: true,
                    partSize: 1024 * 1024 * 5
                },

                // 事件
                callbacks: {
            
                    // 上传前
                    onUpload: function (id, name) {},
                    
                    // 上传完成
                    onComplete: function (id, name, res, xhr) {

                        var file = this.getFile(id)
                        var chunk_index = file.chunk_index
                        var local_id = res.data.id

                        // 找到对应的 pic 项并赋值
                        for (var i = 0, len = pics.length; i < len; i++) {

                            if (pics[i].index == chunk_index) {

                                pics[i].local_id = local_id
                            }
                        }
                    },
            
                    // 全部完成
                    onAllComplete: function (succ_arr, fail_arr) {
                        
                        // 该方法有bug, 可能会调用多次
                        if (succ_arr.length == 0 && fail_arr.length == 0) return

                        // 如果有图片上传失败给出提示
                        if (fail_arr.length > 0) {

                            console.warn('同步失败, 有图片上传失败')
                            return
                        }

                        
                        var post_pics = []
                        for (var i = 0, len = pics.length; i < len; i++) {

                            var pics_item = pics[i]
                            var post_pics_item = {
                                type: pics_item.type,
                                width: pics_item.width,
                                height: pics_item.height,
                                url: pics_item.url,
                            }

                            // 如果是本地图片还要加上本地图片的id
                            if (pics_item.type == 'local') {

                                post_pics_item['local_id'] = pics_item.local_id
                            }
                            post_pics.push(post_pics_item)
                        }

                        // 提交同步请求
                        $.post('http://test.qianzhiyu.net/tb/sync/pc', {

                            goods_id: goods_id,
                            template_id: template_id,
                            editor_data: JSON.stringify(editor_data),
                            pics: JSON.stringify(post_pics),
                        }).then(function (res) {

                            console.log(res);
                        })
                    },
            
                    // 出错
                    onError: function (id, name, reason, xhr) {},
            
                    // 过程
                    onProgress: function (id, name, upload_bytes, total_bytes) {},
            
                    // 全部过程
                    onTotalProgress: function (total_upload_bytes, total_bytes) {},
            
                    // 分片上传前
                    onUploadChunk: function (id, name, chunk_data) {
                        return Promise.resolve({
                            params: { real_filename: this.getFile(id).name }
                        })
                    },

                    // 状态改变
                    onStatusChange: function (id, old_status, new_status) {},
                },
            })


            // 执行生成图片命令
            editor.generateImg.exec({

                // 只生成本地图片项
                chunk_pos: (function () {

                    // 取出本地图片项
                    var local_pics = []
                    for (var i = 0, len = pics.length; i < len; i++) {

                        if (pics[i].type == 'local') {

                            local_pics.push(pics[i])
                        }
                    }

                    return local_pics
                })(),

                // 更新进度
                onProgress: function (chunk_offset, total_length) {

                    var progress = Math.floor(chunk_offset / total_length * 100)
                    $progress.html(progress + '%')
                },


                // 分片完成
                onChunkComplete: function (pos_item, base64_item) {

                    // 生成随机名称, 按顺序存入生成的blob
                    var filename = Date.now().toString() + blob_promises.length + '.png'
                    var blob = base64ToFile(base64_item.base64, filename).then(function (blob) {

                        // 即上面的 pos_index
                        blob.chunk_index = pos_item['index']
                        return blob
                    })

                    var index = base64_item['index']
                    blob_promises[index] = blob
                },


                // 全部生成完成
                onComplete: function (base64_arr) {

                    // 等待所有blob生成完之后就可以开始上传了
                    Promise.all(blob_promises).then(function (blobs) {

                        uploader.addFiles(blobs)
                    })
                }
            })
        })
    })()


    // 插入一个原图模块
    ;(function () {

        var $btn = $('.J_add-origin-mk')
        $btn.on('click', function () {

            var link = prompt("输入淘宝图片链接", 'https://img.alicdn.com/imgextra/i3/446034741/O1CN01UbpgQN1ktQTBFCHw9_!!446034741.png')
            if (link == null) return

            var mk_index = prompt('插入到第几个模块后面', '0')
            var $mk = editor.mkMethods.getMkByIndex(mk_index)


            var img = new Image()
            img.onload = function () {

                var width = img.width
                var height = img.height

                var $el = editor.mkMethods.add(height, $mk, 'after')
                editor.groupMethods.picMethods.add($el, link, width, height, 0, 0)
            }

            img.src = link
        })
    })()


    // 添加热区
    ;(function () {

        var $btn = $('.J_add-hotspot')
        $btn.on('click', function () {

            var mk_index = parseInt(prompt('要添加到哪个模块里, 输入下标', '0'))
            mk_index = isNaN(mk_index) ? 0 : mk_index
            mk_index = mk_index < 0 ? 0 : mk_index

            var link = prompt('热区链接', 'https://item.taobao.com/item.htm?spm=a2oq0.12575281.0.0.50111debZIVzZ0&ft=t&id=584181130977').toString()
            link = link == '' ? 'https://item.taobao.com/item.htm?spm=a2oq0.12575281.0.0.50111debZIVzZ0&ft=t&id=584181130977' : link

            var $mk = $entity.find('[el-type="mk"]').eq(mk_index)
            editor.groupMethods.hotspotMethods.add($mk, link)
        })
    })()


    // 建立websocket连接
    ;(function () {

        var $log = $('.log')

        var login_msg_id = Date.now()
        var ws = new WebSocket('ws://121.40.178.252:30093')
        ws.onopen = function () {

            $log.append('<p>websocket 连接成功</p>')

            // 心跳
            setInterval(function () {

                ws.send(Date.now())
            }, 30000)

            // 登录
            ws.send(JSON.stringify({
                handle: 'login',
                msg_id: login_msg_id,
                data: {
                    uid: 113,
                }
            }))
        }
        ws.onmessage = function (res) {

            $log.append('<p>'+ res +'</p>')
        }
        ws.onerror = function (e) {

            console.log(e);
        }
    })()
})()