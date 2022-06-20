此文件夹放淘宝相关的逻辑块


1. 每个逻辑类, 应该必须有handle方法, handle方法必须注释好入参需要什么, 出参返回什么
2. 逻辑类涉及调用三方接口, 必须自动保存报错信息并记录日志
    1. 保存代码错误抛出的信息, Exception类, 全部信息
    2. 保存三方接口返回的错误信息, 全部信息
    3. 错误信息加上前缀标注  [自定义接口抛错: app\extend\tb\TbGetFolder]