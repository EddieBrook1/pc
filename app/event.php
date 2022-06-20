<?php
// 事件定义文件
return [
    'bind'      => [
    ],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],

        // 当客户的cookie被更新
        'UpdateUserCookie' => ['app\listener\UpdateUserCookie'],
    ],

    'subscribe' => [
    ],
];
