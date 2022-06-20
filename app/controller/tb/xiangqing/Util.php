<?php
namespace app\controller\tb\xiangqing;

use app\model\OldPaymentModel;

class Util {

    // 支付是否到期
    public static function isPayExpire ($uid, $template_id) {
        $payment = OldPaymentModel::where('user_id', $uid)
            ->where('template_id', $template_id)
            ->find();

        return $payment->expiretime > time();
    }

}