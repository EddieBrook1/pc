<?php

return [
    // 默认使用的数据库连接配置
    'default'         => env('database.driver', 'current'),

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => true,

    // 时间字段取出后的默认时间格式
    // 'datetime_format' => 'Y-m-d H:i:s',
    'datetime_format' => false,

    // 时间字段配置 配置格式：create_time,update_time
    'datetime_field'  => '',

    // 数据库连接配置信息
    'connections'     => [

        // 当前的数据库连接
        'current' => [
            // 数据库类型
            'type'            => env('database_current.type', 'mysql'),
            // 服务器地址
            'hostname'        => env('database_current.hostname', '127.0.0.1'),
            // 数据库名
            'database'        => env('database_current.database', ''),
            // 用户名
            'username'        => env('database_current.username', 'root'),
            // 密码
            'password'        => env('database_current.password', ''),
            // 端口
            'hostport'        => env('database_current.hostport', '3306'),
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => env('database_current.charset', 'utf8'),
            // 数据库表前缀
            'prefix'          => env('database_current.prefix', ''),

            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL
            'trigger_sql'     => env('app_debug', true),
            // 开启字段缓存
            'fields_cache'    => false,
        ],


        // 原先的数据库连接
        'origin' => [
            // 数据库类型
            'type'            => env('database_origin.type', 'mysql'),
            // 服务器地址
            'hostname'        => env('database_origin.hostname', '127.0.0.1'),
            // 数据库名
            'database'        => env('database_origin.database', ''),
            // 用户名
            'username'        => env('database_origin.username', 'root'),
            // 密码
            'password'        => env('database_origin.password', ''),
            // 端口
            'hostport'        => env('database_origin.hostport', '3306'),
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => env('database_origin.charset', 'utf8'),
            // 数据库表前缀
            'prefix'          => env('database_origin.prefix', ''),

            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL
            'trigger_sql'     => env('app_debug', true),
            // 开启字段缓存
            'fields_cache'    => false,
        ],
    ],
];
