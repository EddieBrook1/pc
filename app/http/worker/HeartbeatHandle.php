<?php
namespace app\http\worker;

use app\extend\Util;

class HeartbeatHandle {

    public function exec ($data, $connection) {

        return Util::formatResult([], true, 'EXEC_SUCC');
    }
}