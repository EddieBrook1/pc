<?php

// 存放job名和对应处理类映射
return [
    'koutu' => 'app\job\KoutuJob',
    'sync_upload_pic' => 'app\job\SyncJob',
    'sync_commit' => 'app\job\SyncJob',
];