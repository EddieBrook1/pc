<?php
namespace app\controller\tb\xiangqing;

use app\model\OldPaymentModel;
use app\model\OldTemplateModel;

class XiangqingUtil {

    // 支付是否到期
    public static function isPayExpire ($uid, $template_id) {
        $payment = OldPaymentModel::where('user_id', $uid)
            ->where('template_id', $template_id)
            ->find();

        return $payment->expiretime > time();
    }


    // 模版是否可用
    public static function isTemplateUsable ($template_id) {
        $template = OldTemplateModel::where('template_id', $template_id)
            ->where('templateState', 2)
            ->find();
        return boolval($template);
    }
}