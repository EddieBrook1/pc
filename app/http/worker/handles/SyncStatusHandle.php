<?php
namespace app\http\worker\handles;

use app\extend\Util;
use app\model\SyncMainJobModel;

class SyncStatusHandle {

    public function exec ($data, $connection) {

        $uid = $data['uid'];
        $goods_id = $data['goods_id'];
        
        // 该商品是否有未完成的同步任务, 返回最新的一条
        $syncJob = SyncMainJobModel::field('id,uid,template_id,goods_id,status_code,progress')
            ->where('uid', $uid)
            ->where('goods_id', $goods_id)
            ->order('create_time', 'desc')
            ->find();

        $result = $syncJob == null
            ? []
            : Util::formatJson([
                'last_item' => $result
            ], true);
    }
}