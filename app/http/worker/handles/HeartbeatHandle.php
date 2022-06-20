<?php
namespace app\http\worker\handles;

use app\extend\Util;

class HeartbeatHandle {

    public function exec ($data, $connection) {

        return Util::formatResult([], true, 'EXEC_SUCC');
    }
}