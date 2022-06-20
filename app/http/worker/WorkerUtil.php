<?php
namespace app\http\worker;



class WorkerUtil {

    public static function buildKey ($worker_id, $connection_id) {

        return 'k_' . $worker_id . '_' . $connection_id;
    }
}