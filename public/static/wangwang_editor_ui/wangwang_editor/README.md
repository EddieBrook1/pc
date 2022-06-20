# 旺铺编辑器



## 快速开始

依赖 `jquery3.6.0`，`polyfill`，`html2canvas`；`data.js` 为演示数据，向全局添加一个 `data` 变量

~~~html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>旺铺编辑器</title>
    <link rel="stylesheet" href="./index.css">
</head>
<body>


<div id="editor" class="editor"></div>

<script src="./jquery3.6.0.min.js"></script>
<script src="./data.js"></script>
<script src="./polyfill.min.js"></script>
<script src="./html2canvas.min.js"></script>
<script src="./index.js"></script>
<script>

    window.editor = Editor('#editor', data)
</script>
</body>
</html>
~~~



## editor 对象

调用 `Editor('#editor', data)` 后会返回一个对象，该对象下有若干方法，本文把它称作 `editor` 对象



## 编辑器方法 - editor.editorMethods

#### 获取编辑器所有图片链接(getAllImgSrc)

`editor.editorMethods.getAllImgSrc()`

**入参**

无

**返回值**

图片链接地址数组，形如：`['http://img.xxx.com', 'xxx.xxx.png']`

**示例**

~~~javascript
editor.editorMethods.getAllImgSrc()
~~~



---

#### 获取编辑器数据(getData)

`editor.editorMethods.getData()`

**入参**

无

**返回值**

二维数组，形如：`[{ boxStyle: {}, groups: {}, category: 0 }, xxx, xxx]`

**示例**

~~~javascript
editor.editorMethods.getData()
~~~



---

#### 获取编辑器高度(getHeight)

`editor.editorMethods.getHeight()`

**入参**

无

**返回值**

数值，为编辑器的高度

**示例**

~~~javascript
editor.editorMethods.getHeight()
~~~





## 模块方法 - editor.mkMethods

#### 添加(add)

`editor.mkMethods.add(height, $dist_mk, pos)`

**入参**

- `height`  **必选**，新模块高度
- `$dist_mk`  可选，要插入到哪个模块后面(或前面)，如果此参数为空则追加到编辑器末尾
- `pos`  可选，要插入到`$dist_mk`的前面还是后面，可选值 `before`或`after`，默认为`after`

**返回值**

JQDOM，为新插入的模块

**示例**

~~~javascript
// 向编辑器追加一个 300px 高的新模块
editor.mkMethods.add(300)

// 在 $dist_mk 后添加一个模块
var $dist_mk = $('#editor .entity').find('[el-type="mk"]').eq(0)
editor.mkMethods.add(300, $dist_mk)
~~~



---

#### 复制(copy)

`editor.mkMethods.copy($target_mk, $dist_mk, pos)`

**入参**

- `$target_mk`  **必选**，要复制的模块
- `$dist_mk` **必选**，要复制到哪个模块的后面(或前面)
- `pos`  可选，`$target_mk` 要插入到 `$dist_mk` 的前面或后面，可选值`before`或`after`，默认为`after`

**返回值**

无

**示例**

~~~javascript
// 复制一个 $mk0 到 $mk1 后面
var $editor = $('#editor .entity')
var $mks = $editor.find('[el-type="mk"]')
var $mk0 = $mks.eq(0)
var $mk1 = $mks.eq(1)
editor.mkMethods.copy($mk0, $mk1)

// 复制到前面
editor.mkMethods.copy($mk0, $mk1, 'before')
~~~



---

#### 剪切(cut)

`editor.mkMethods.cut($target_mk, $dist_mk, pos)`

**入参**

- `$target_mk`  **必选**，要剪切的模块
- `$dist_mk` **必选**，要剪切到哪个模块的后面(或前面)
- `pos`  可选，`$target_mk` 要插入到 `$dist_mk` 的前面或后面，可选值`before`或`after`，默认为`after`

**返回值**

无

**示例**

~~~javascript
// 剪切 $mk0 到 $mk1 后面
var $editor = $('#editor .entity')
var $mks = $editor.find('[el-type="mk"]')
var $mk0 = $mks.eq(0)
var $mk1 = $mks.eq(1)
editor.mkMethods.cut($mk0, $mk1)

// 剪切到前面
editor.mkMethods.cut($mk0, $mk1, 'before')
~~~



---

#### 删除(del)

`editor.mkMethods.del($mk)`

**入参**

- `$mk` **必选**，要删除的模块

**返回值**

无

**示例**

~~~javascript
// 删除编辑器下的第一个模块
var $mk = $('#editor .entity').find('[el-type="mk"]').eq(0)
editor.mkMethods.del($mk)
~~~



---

#### 获取模块数据(getData)

`editor.mkMethods.getData($mk)`

**入参**

- `$mk`  **必选**，要获取数据的模块

**返回值**

对象，形如：`{boxStyle: {}, groups: [], category: 0}`

**示例**

~~~javascript
// 获取编辑器下第一个模块的数据
var $mk = $('#editor .entity').find('[el-type="mk"]').eq(0)
editor.mkMethods.getData($mk)
~~~



---

#### 获取模块下所有组件最大的 z-index 值(getMaxIndex)

`editor.mkMethods.getMaxIndex($mk)`

**入参**

- `$mk` **必选**，要获取最大z-index 的模块

**返回值**

数值，模块下所有组件里最大的 z-index 值

**示例**

~~~javascript
// 获取编辑器下第一个模块下所有组件里最大的 z-index 值
var $mk = $('#editor .entity').find('[el-type="mk"]').eq(0)
editor.mkMethods.getMaxIndex($mk)
~~~



---

#### 通过下标获取模块(getMkByIndex)

`editor.mkMethods.getMkByIndex(index)`

**入参**

- `index`  **必选**，0开始的下标

**返回值**

JQDOM，对应的模块

**示例**

~~~javascript
// 获取编辑器下第一个模块
editor.mkMethods.getMkByIndex(0)
~~~



---

#### 设置模块高度(setHeight)

`editor.mkMethods.setHeight($mk, height)`

**入参**

- `$mk`  **必选**，模块

- `height`  **必选**，模块高度

**返回值**

无

**示例**

~~~javascript
// 设置第一个模块高度为500px
var $mk = editor.mkMethods.getMkByIndex(0)
editor.mkMethods.setHeight($mk, 500)
~~~



## 组件方法 - 公用 - editor.groupMethods

> 为方便行文，图片、文字、热区、视频、动图统称为组件，热区可看成是没有实质内容的组件

#### 居中(centerGroupItem)

`editor.groupMethods.centerGroupItem($group_item, axis)`

**入参**

- `$group_item`  **必选**，要居中的组件
- `axis` 可选，可选值`x`或`y`， `x`为左右居中，`y`为上下居中

**返回值**

无

**示例**

~~~javascript
var $editor = $('#editor .entity')
var $mks = $editor.find('[el-type="mk"]')

// 居中图片组件
var $img = $mks.eq(0).find('[el-type="component-pic"]').eq(0)
editor.groupMethods.centerGroupItem($img)

// 居中文字组件
var $text = $mks.eq(1).find('[el-type="component-text"]').eq(0)
editor.groupMethods.centerGroupItem($text)

// 居中动图组件
var $gif = $mks.eq(0).find('[el-type="component-gif"]').eq(0)
editor.groupMethods.centerGroupItem($gif)

// 居中视频组件
var $video = $mks.eq(0).find('[el-type="component-video"]').eq(0)
editor.groupMethods.centerGroupItem($video)

// 居中热区
var $hotspot = $mks.eq(0).find('[el-type="hotspot"]').eq(0)
editor.groupMethods.centerGroupItem($hotspot)
~~~



---

#### 复制(copy)

> 图片组件、文字组件、动图组件、视频组件、热区都可以用这个方法

`editor.groupMethods.copy($target, $dist_mk, left, top)`

**入参**

- `$target`  **必选**，要复制的组件
- `$dist_mk`  **必选**，要复制到哪个模块里
- `left`  可选，复制组件的left值，数值或'auto'，默认居中，left <= 模块宽 - 组件宽
- `top`  可选，复制组件的top值，数值或'auto'，默认居中，top <= 模块高 - 组件高

**返回值**

无

**示例**

~~~javascript
// 复制 $mk0 下的第一个图片组件到 $mk1 下，位置上下左右居中
var $mk0 = editor.mkMethods.getMkByIndex(0)
var $mk1 = editor.mkMethods.getMkByIndex(1)
var $img = $mk0.find('[el-type="component-pic"]').eq(0)
editor.groupMethods.copy($img, $mk1)

// 左右居中，距离上面100px
editor.groupMethods.copy($img, $mk1, 'auto', 100)

// 上下居中，距离左边100px
editor.groupMethods.copy($img, $mk1, 100, 'auto')
~~~



---

#### 剪切(cut)

> 图片组件、文字组件、动图组件、视频组件、热区都可以用这个方法

`editor.groupMethods.cut($target, $dist_mk, left, top)`

**入参**

- `$target`  **必选**，要剪切的组件
- `$dist_mk`  **必选**，要剪切到哪个模块里
- `left`  可选，剪切组件的left值，数值或'auto'，默认居中
- `top`  可选，剪切组件的top值，数值或'auto'，默认居中

**返回值**

无

**示例**

~~~javascript
// 剪切 $mk0 下的第一个图片组件到 $mk1 下，位置上下左右居中
var $mk0 = editor.mkMethods.getMkByIndex(0)
var $mk1 = editor.mkMethods.getMkByIndex(1)
var $img = $mk0.find('[el-type="component-pic"]').eq(0)
editor.groupMethods.cut($img, $mk1)

// 左右居中，距离上面100px
editor.groupMethods.cut($img, $mk1, 'auto', 100)

// 上下居中，距离左边100px
editor.groupMethods.cut($img, $mk1, 100, 'auto')
~~~



---

#### 删除(del)

> 图片组件、文字组件、动图组件、视频组件、热区都可以用这个方法

`editor.groupMethods.del($group_item)`

**入参**

- `$group_item`  **必选**，要删除的项

**返回值**

无

**示例**

~~~javascript
// 删除 $mk0 下第一个图片组件
var $mk0 = editor.mkMethods.getMkByIndex(0)
var $img = $mk0.find('[el-type="component-pic"]').eq(0)

editor.groupMethods.del($img)
~~~



---

#### 获取组件数据(getData)

> 图片组件、文字组件、动图组件、视频组件、热区都可以用这个方法

`editor.groupMethods.getData($group_item)`

**入参**

- `$group_item`  **必选**，要获取数据的项

**返回值**

对象，形如：`{boxStyle: {}, groups: [], category: 0}`

**示例**

~~~javascript
// 获取热区数据
var $hotspot = editor.mkMethods.getMkByIndex(0).find('[el-type="hotspot"]')
editor.groupMethods.getData($hotspot)
~~~



---

#### 上浮(goUp)

> 使组件的 z-index += 1
>
> 图片组件、文字组件使用才有意义。动图组件、视频组件，模块里只有1个；热区永远在最上层

`editor.groupMethods.goUp($component)`

**入参**

- `$component`  **必选**，要上浮的组件

**返回值**

无

**示例**

~~~javascript
var $img = editor.mkMethods.getMkByIndex(0).find('[el-type="component-pic"]').eq(0)
editor.groupMethods.goUp($img)
~~~



---

#### 下沉(goDown)

> 使组件的 z-index -= 1，z-index不小于0
>
> 图片组件、文字组件使用才有意义。动图组件、视频组件，模块里只有1个；热区永远在最上层

`editor.groupMethods.goDown($component)`

**入参**

- `$component`  **必选**，要下沉的组件

**返回值**

无

**示例**

~~~javascript
var $img = editor.mkMethods.getMkByIndex(0).find('[el-type="component-pic"]').eq(0)
editor.groupMethods.goDown($img)
~~~



## 组件方法 - 图片 - editor.groupMethods.picMethods

#### 自适应(adapt)

> 图片组件由外层div和里层img组成，各自都可以设置 width 和 height属性，调用此方法后会根据外层div宽高缩放里层img，使图片尽可能完整地显示

`editor.groupMethods.picMethods.adapt($component)`

**入参**

- `$component`  **必选**，要自适应的图片组件

**返回值**

无

**示例**

~~~javascript
var $img = editor.mkMethods.getMkByIndex(0).find('[el-type="component-pic"]').eq(0)
editor.groupMethods.picMethods.adapt($img)
~~~



---

#### 添加图片(add)

`editor.groupMethods.picMethods.add($mk, img_url, width, height, left, top)`

**入参**

- `$mk`  **必选**，图片添加到哪个模块
- `img_url`  **必选**，图片链接
- `width`  可选，默认为图片真实宽
- `height` 可选，默认为图片真实高
- `left`  可选，数值或'auto'，默认为'auto'，'auto'为居中
- `top`  可选，数值或'auto'，默认为'auto'，'auto'为居中

**返回值**

Promise实例，只有resolve结果，`resolve($el)` 或 `resolve(false)`

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
editor.groupMethods.picMethods.add($mk, 'xxxxxx').then(function (res) {
    console.log(res)
})
~~~



---

#### 获取图片链接(getLink)

`editor.groupMethods.picMethods.getLink($component)`

**入参**

- `$component`  **必选**，图片组件

**返回值**

图片链接

**示例**

~~~javascript
var $img = editor.mkMethods.getMkByIndex(0).find('[el-type="component-pic"]').eq(0)
var src = editor.groupMethods.picMethods.getLink($img)
console.log(src)
~~~



---

#### 设置图片链接(setLink)

`editor.groupMethods.picMethods.setLink($component, url)`

**入参**

- `$component`  **必选**，图片组件
- `url`  **必选**，图片链接

**返回值**

Promise实例，只有resolve结果，`resolve(true)` 或 `resolve(false)`

**示例**

~~~javascript
var $img = editor.mkMethods.getMkByIndex(0).find('[el-type="component-pic"]').eq(0)
var src = editor.groupMethods.picMethods.setLink($img, 'xxxx').then(function (res) {
    console.log(res)
})
~~~



## 组件方法 - 文字 - editor.groupMethods.textMethods

#### 添加文字(add)

`editor.groupMethods.textMethods.add($mk, text, placeholder, width, height, left, top)`

**入参**

- `$mk`  **必选**，文字组件要添加到哪个模块里
- `text`  **必选**，文字内容
- `placeholder`  可选，内容为空时的占位文字
- `width`  可选，组件宽，默认为200px
- `height`  可选，组件高，默认为40px
- `left`  可选，数值或'auto'，默认为'auto'，'auto'为居中
- `top`  可选，数值或'auto'，默认为'auto'，'auto'为居中

**返回值**

JQDOM，新添加的文字组件

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
editor.groupMethods.textMethods.add($mk, '文字内容')
~~~



---

#### 居左、居中、居右、粗体切换、斜体切换、大小写切换、字间距、字体方向

- 居左  `editor.groupMethods.textMethods.toLeft($component)`
- 居中  `editor.groupMethods.textMethods.toCenter($component)`
- 居左  `editor.groupMethods.textMethods.toRight($component)`
- 粗体切换   `editor.groupMethods.textMethods.toggleBold($component)`
- 斜体切换   `editor.groupMethods.textMethods.toggleItalic($component)`
- 大小写切换   `editor.groupMethods.textMethods.toggleCase($component, flag)`  flag = true 切换成大写，flag = false 切换成小写
- 字间距   `editor.groupMethods.textMethods.setWordSpace($component, val)`  val 数值
- 字体方向   `editor.groupMethods.textMethods.toggleWritingMode($component)`



## 组件方法 - 动图 - editor.groupMethods.gifMethods

#### 添加动图(add)

`editor.groupMethods.gifMethods.add($mk, link, width, height)`

**入参**

- `$mk`  **必选**，动图添加到哪个模块
- `link`  **必选**，动图链接
- `width`  可选，默认为动图真实宽
- `height` 可选，默认为动图真实高

**返回值**

Promise实例，只有resolve结果，`resolve($el)` 或 `resolve(false)`

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
editor.groupMethods.gifMethods.add($mk, 'xxxxxx').then(function (res) {
    console.log(res)
})
~~~



---

#### 获取动图链接(getLink)

`editor.groupMethods.gifMethods.getLink($component)`

**入参**

- `$component`  **必选**，动图组件

**返回值**

动图链接

**示例**

~~~javascript
var $gif = editor.mkMethods.getMkByIndex(0).find('[el-type="component-gif"]').eq(0)
var src = editor.groupMethods.gifMethods.getLink($gif)
console.log(src)
~~~



---

#### 设置动图链接(setLink)

> 动图组件宽度会被设置成模块宽度，高度根据宽度比例来缩放

`editor.groupMethods.gifMethods.setLink($component, url)`

**入参**

- `$component`  **必选**，动图组件
- `url`  **必选**，动图链接

**返回值**

Promise实例，只有resolve结果，`resolve(true)` 或 `resolve(false)`

**示例**

~~~javascript
var $gif = editor.mkMethods.getMkByIndex(0).find('[el-type="component-gif"]').eq(0)
var src = editor.groupMethods.gifMethods.setLink($gif, 'xxxx').then(function (res) {
    console.log(res)
})
~~~



## 组件方法 - 视频 - editor.groupMethods.videoMethods

#### 添加视频(add)

`editor.groupMethods.videoMethods.add($mk, link, width, height)`

**入参**

- `$mk`  **必选**，视频添加到哪个模块
- `link`  **必选**，视频链接
- `width`  可选，默认为视频真实宽
- `height` 可选，默认为视频真实高

**返回值**

Promise实例，只有resolve结果，`resolve($el)` 或 `resolve(false)`

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
editor.groupMethods.videoMethods.add($mk, 'xxxxxx').then(function (res) {
    console.log(res)
})
~~~



---

#### 获取视频链接(getLink)

`editor.groupMethods.videoMethods.getLink($component)`

**入参**

- `$component`  **必选**，视频组件

**返回值**

图片链接

**示例**

~~~javascript
var $video = editor.mkMethods.getMkByIndex(0).find('[el-type="component-video"]').eq(0)
var src = editor.groupMethods.videoMethods.getLink($video)
console.log(src)
~~~



---

#### 设置视频链接(setLink)

> 视频组件宽度会被设置成模块宽度，高度根据宽度比例来缩放

`editor.groupMethods.videoMethods.setLink($component, url)`

**入参**

- `$component`  **必选**，视频组件
- `url`  **必选**，视频链接

**返回值**

Promise实例，只有resolve结果，`resolve(true)` 或 `resolve(false)`

**示例**

~~~javascript
var $video = editor.mkMethods.getMkByIndex(0).find('[el-type="component-video"]').eq(0)
var src = editor.groupMethods.videoMethods.setLink($video, 'xxxx').then(function (res) {
    console.log(res)
})
~~~



---

#### 播放视频(play)

`editor.groupMethods.videoMethods.play($component)`

**入参**

- `$component`  **必选**，视频组件

**返回值**

无

示例

~~~javascript
var $video = editor.mkMethods.getMkByIndex(0).find('[el-type="component-video"]').eq(0)
var src = editor.groupMethods.videoMethods.play($video)
~~~



---

#### 暂停视频(pause)

`editor.groupMethods.videoMethods.pause($component)`

**入参**

- `$component`  **必选**，视频组件

**返回值**

无

示例

~~~javascript
var $video = editor.mkMethods.getMkByIndex(0).find('[el-type="component-video"]').eq(0)
var src = editor.groupMethods.videoMethods.pause($video)
~~~



---

#### 静音(mute)

> 视频组件默认是静音的

`editor.groupMethods.videoMethods.mute($component)`

**入参**

- `$component`  **必选**，视频组件

**返回值**

无

示例

~~~javascript
var $video = editor.mkMethods.getMkByIndex(0).find('[el-type="component-video"]').eq(0)
var src = editor.groupMethods.videoMethods.mute($video)
~~~



---

#### 开启声音(phonic)

`editor.groupMethods.videoMethods.phonic($component)`

**入参**

- `$component`  **必选**，视频组件

**返回值**

无

示例

~~~javascript
var $video = editor.mkMethods.getMkByIndex(0).find('[el-type="component-video"]').eq(0)
var src = editor.groupMethods.videoMethods.phonic($video)
~~~



## 组件方法 - 热区 - editor.groupMethods.hotspotMethods

#### 添加热区(add)

`editor.groupMethods.hotspotMethods.add($mk, link, width, height, left, top)`

**入参**

- `$mk`  **必选**，热区添加到哪个模块
- `link`  **必选**，热区链接
- `width`  可选，默认为150px
- `height` 可选，默认为150px
- `left` 可选，数值或'auto'，默认为'auto'，'auto'为居中
- `top` 可选，数值或'auto'，默认为'auto'，'auto'为居中

**返回值**

JQDOM，新添加的热区组件

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
editor.groupMethods.hotspotMethods.add($mk, 'xxxxxx')
~~~



---

#### 使模块下所有热区置顶(allToTop)

`editor.groupMethods.hotspotMethods.allToTop($mk)`

**入参**

- `$mk`  **必选**，模块

**返回值**

无

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
editor.groupMethods.hotspotMethods.allToTop($mk)
~~~



---

#### 获取热区链接

`editor.groupMethods.hotspotMethods.getLink($hotspot)`

**入参**

- `$hotspot` **必选**，热区

**返回值**

热区链接

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
var $hotspot = $mk.find('[el-type="hotspot"]')
editor.groupMethods.hotspotMethods.getLink($hotspot)
~~~



---

#### 设置热区链接

`editor.groupMethods.hotspotMethods.setLink($hotspot, link)`

**入参**

- `$hotspot` **必选**，热区
- `link`  **必选**，热区链接

**返回值**

无

**示例**

~~~javascript
var $mk = editor.mkMethods.getMkByIndex(0)
var $hotspot = $mk.find('[el-type="hotspot"]')
editor.groupMethods.hotspotMethods.setLink($hotspot, link)
~~~



## 辅助线 - editor.guideLine

#### 启用(enable)

`editor.guideLine.enable()`

**入参**

无

**返回值**

无

**示例**

~~~javascript
editor.guideLine.enable()
~~~



---

#### 禁用(disable)

`editor.guideLine.disable()`

**入参**

无

**返回值**

无

**示例**

~~~javascript
editor.guideLine.disable()
~~~



## 生成图片 - editor.generateImg

#### 生成(exec)

> 默认不分片，生成一张超长的图，如需分片，需手动计算每张图的偏移量，见下面示例。
>
> 带有视频模块、动图模块的编辑器暂时不能生成图片，或许会报错

`editor.generateImg.exec(param)`

**入参**

- `param` 对象
  - `param.onStart`  可选，函数，开始前调用，只会执行一次，返回一个拒绝promise可以阻止图片生成
  - `param.onChunk`  可选，函数，分片生成前调用，每次生成分片前都会调用，返回一个拒绝promise可以阻止后续分片生成
  - `param.onProgress`  可选，函数，分片生成后调用，每次生成分片后调用，传入3个参数
    - `chunk_offset`  已上传的分片数量
    - `total_length`  全部分片数量
    - `base64_arr_now`  已生成分片的base64码
  - `param.onComplete`  可选，函数，全部分片生成完成后调用，传入一个数组
    - `result_arr`  全部分片的base64码
  - `param.onStop`  可选，函数，手动停止生成后调用，传入一个数组
    - `base64_arr_now`  已生成的分片base64码
  - `param.onError`  可选，函数，生成分片前，检查图片发现有死链时调用，传入一个加载失败的图片链接数组
    - `err_links`  加载失败的图片地址
  - `param.chunk_pos`  可选，数组，形如： `[{ offset: 0, limit: 100 }, { offset: 100, limit: 200 }]`，其中 `offset` 是从哪里开始截取，`limit`是截多少

**返回值**

进程号，可用于手动终止图片生成

**示例**

~~~javascript
// 生成 1 张超长的图
editor.generateImg.exec({
    onComplete: function (result_arr) {
        console.log(result_arr[0])
    }
})

// 假设图片有 2100 高，然后把它分成 3 份，每张高 700px
editor.generateImg.exec({
    chunk_pos: [{
        offset: 0,
        limit: 700
    }, {
        offset: 700,
        limit: 700,
    }, {
        offset: 1400,
        limit: 700,
    }],
    onComplete: function (result_arr) {
        console.log(result_arr)
    }
})

// 完整示例
editor.generateImg.exec({
    chunk_pos: [{
        offset: 0,
        limit: 700
    }, {
        offset: 700,
        limit: 700,
    }, {
        offset: 1400,
        limit: 700,
    }],
    
    onStart: function () {
      
        // return Promise.reject()
    },
    
    onChunk: function () {
      
        // return Promise.reject()
    },
    
    onProgress: function (chunk_offset, total_length, base64_arr_now) {
        
        console.log('当前进度:' + parseInt(chunk_offset/total_length*100) + '%')
    },
    
    onComplete: function (result_arr) {

        console.log(result_arr)
    },
    
    onStop: function (base64_arr_now) {
        
        console.log(base64_arr_now)
    },
    
    onError: function (err_links) {
        
        console.log(err_links)
    }
})
~~~



---

#### 终止(stop)

`generateImg.stop(process_num)`

**入参**

- `process_num` **必选**，进程号，调用 `generateImg.exec` 的返回值

**返回值**

无

**示例**

~~~javascript
var process_num = editor.generateImg.exec()
editor.generateImg.stop(process_num)
~~~



## 事件

**事件统一用 `on` 方法侦听**

**示例**

~~~javascript
// 侦听编辑器高度变化
editor.on('editorHeightChange', function (height) {
    
    console.log(height)
})
~~~



#### 编辑器高度改变(editorHeightChange)

**回调入参**

- `height` 当前编辑器高度

**示例**

~~~javascript
// 侦听编辑器高度变化
editor.on('editorHeightChange', function (height) {
    
    console.log(height)
})
~~~

