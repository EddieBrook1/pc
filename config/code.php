<?php

// 存储自定义的响应码
return [

    'DEFAULT' => [
        'code' => 0,
        'msg' => '',
    ],

    'UN_LOGIN' => [
        'code' => 1001,
        'msg' => '未登录',
    ],

    'SERVICE_OK' => [
        'code' => 1002,
        'msg' => '服务可用'
    ],

    'SYNC_JOB_EXIST' => [
        'code' => 1003,
        'msg' => '该商品正在同步, 请勿重复操作'
    ],

    'PARAM_ERR' => [
        'code' => 1004,
        'msg' => '参数错误',
    ],

    'TB_PIC_SPACE_FULL' => [
        'code' => 1005,
        'msg' => '您的淘宝图片空间容量不足，请登录图片空间（tu.taobao.com）清理图片或订购存储功能包',
    ],

    'TB_UN_LOGIN' => [
        'code' => 1006,
        'msg' => '您的淘宝未登录或授权登录已过期，请重新授权登录千绘后使用',
    ],

    'SYSTEM_ERROR' => [
        'code' => 1007,
        'msg' => '系统错误',
    ],
    
    'SYNCING' => [
        'code' => 1008,
        'msg' => '正在同步...',
    ],

    'SYNC_COMPLETE' => [
        'code' => 1009,
        'msg' => '同步成功',
    ],

    'SYNC_PROCESSING_DATA' => [
        'code' => 1010,
        'msg' => '正在处理模版数据...'
    ],

    'SYNC_SUBMIT_SUCC' => [
        'code' => 1011,
        'msg' => '同步请求提交成功',
    ],

    'USER_UNDEFINED' => [
        'code' => 1012,
        'msg' => '用户不存在',
    ],

    'LOGIN_SUCC' => [
        'code' => 1013,
        'msg' => '登录成功',
    ],

    'LOGIN_REPEAT' => [
        'code' => 1014,
        'msg' => '您已登录, 请勿重复操作'
    ],

    'UPDATE_SUCC' => [
        'code' => 1015,
        'msg' => '更新成功',
    ],

    'ILLEGAL_REQUEST' => [
        'code' => 1016,
        'msg' => '非法请求',
    ],

    'SYNC_PAUSE' => [
        'code' => 1017,
        'msg' => '同步暂停',
    ],

    'EXEC_SUCC' => [
        'code' => 1018,
        'msg' => '操作成功',
    ],

    'UPLOAD_SUCC' => [
        'code' => 1019,
        'msg' => '上传成功',
    ],

    'UPLOAD_FAIL' => [
        'code' => 1020,
        'msg' => '上传失败',
    ],

    'UPLOAD_TYPE_ERR' => [
        'code' => 1021,
        'msg' => '上传失败, 文件类型不允许',
    ],

    'SYNC_HISTORY' => [
        'code' => 1022,
        'msg' => '同步记录',
    ],

    'ADD_SUCC' => [
        'code' => 1023,
        'msg' => '新增成功',
    ],

    'EXEC_FAIL' => [
        'code' => 1024,
        'msg' => '操作失败',
    ],

    'GET_SUCC' => [
        'code' => 1025,
        'msg' => '获取成功',
    ],

    'API_ERROR' => [
        'code' => 1026,
        'msg' => '接口异常',
    ],

    'UPLOAD_FILENAME_ERR' => [
        'code' => 1027,
        'msg' => '上传失败, 文件名含有非法字符',
    ],

    'DIR_NAME_REPEAT_ERR' => [
        'code' => 1028,
        'msg' => '文件夹命名重复',
    ],

    'DIR_FID_NULL_ERR' => [
        'code' => 1028,
        'msg' => '父目录不存在',
    ],

    'TEMPLATE_NULL_ERR' => [
        'code' => 1029,
        'msg' => '模版不存在',
    ],

    'SAVE_SUCC' => [
        'code' => 1030,
        'msg' => '保存成功',
    ],

    'SAVE_ERR' => [
        'code' => 1031,
        'msg' => '保存失败',
    ],

    'TB_WIRELESS_LINK_ERR' => [
        'code' => 1032,
        'msg' => '热区链接非淘系无线端链接',
    ],

    'AUTH_XHEIGHT_ERR' => [
        'code' => 1033,
        'msg' => '您没有超高权限',
    ],

    'AUTH_HDV_ERR' => [
        'code' => 1034,
        'msg' => '您没有超清权限',
    ],

    'AUTH_HGIF_ERR' => [
        'code' => 1035,
        'msg' => '您没有不限高动图权限',
    ],

    'AUTH_VIDEO_ERR' => [
        'code' => 1036,
        'msg' => '您没有横版视频权限',
    ],

    'AUTH_HVIDEO_ERR' => [
        'code' => 1037,
        'msg' => '您没有竖版视频权限',
    ],

    'AUTH_NOTGIF_ERR' => [
        'code' => 1038,
        'msg' => '您未开通动图权限'
    ],

    'AUTH_NOTVIDEO_ERR' => [
        'code' => 1039,
        'msg' => '您未开通视频权限',
    ],

    'SYNC_ERR' => [
        'code' => 1040,
        'msg' => '同步失败'
    ],

    'TOKEN_ERR' => [
        'code' => 1041,
        'msg' => 'token不存在',
    ],

    'SYNC_PREPARE' => [
        'code' => 1042,
        'msg' => '同步预处理',
    ],

    'UNDEFINED' => [
        'code' => 1043,
        'msg' => '不存在',
    ],

    'TB_LOGIN_OK' => [
        'code' => 1044,
        'msg' => '淘宝授权可用',
    ],

    'EXIST' => [
        'code' => 1045,
        'msg' => '存在',
    ],

    'SERVICE_NO' => [
        'code' => 1046,
        'msg' => '您未购买该服务或服务已到期, 请购买或续费后使用'
    ],

    'GET_FAIL' => [
        'code' => 1047,
        'msg' => '获取失败',
    ],
    
    'SYNC_SUBMIT_FAIL' => [
        'code' => 1048,
        'msg' => '同步请求提交失败',
    ],

    'AUTH_VIDEO_LINK_ERR' => [
        'code' => 1049,
        'msg' => '您没有添加视频链接的权限',
    ],
];