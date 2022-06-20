<?php
// +----------------------------------------------------------------------
// | 拼多多参数设置
// +----------------------------------------------------------------------

return [
    // 拼多多接口基本路径
    'api_base_url' => env('pdd.api_base_url', 'https://gw-api.pinduoduo.com/api/router'),
    // 拼多多应用id
    'sj_client_id' => env('pdd.sj_client_id', ''),
    // 拼多多应用密钥
    'sj_secret_key' => env('pdd.sj_secret_key', ''),
    // 上传图片到图片空间时, 用哪个用户的access_token
    'tupian_user_id' => env('pdd.tupian_user_id')
];