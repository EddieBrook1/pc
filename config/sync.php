<?php

return [

    // 上传图片到淘宝的进度的权重, 它与sync_data_weight的合要等于1
    'upload_pic_weight' => 0.7,
    // 数据同步到淘宝的进度的权重, 它与upload_pic_weight的合要等于1
    'sync_data_weight' => 0.3,
    // 失败重试的延迟时间
    'release_delay' => 10,
    // 历史记录保留时长
    'history_expire' => 60 * 60 * 24, // 24小时
];