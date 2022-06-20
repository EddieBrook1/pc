<?php
namespace app\controller\admin;

use think\Request;
use app\BaseController;
use app\model\SettingModel;

class Setting extends BaseController {

    // 设置淘宝开发者的cookie
    public function tbCookieIsv (Request $request) {

        $input = $request->put();
        $isOk = Validator::tbCookieIsv($input);
        if ($isOk !== true) return $this->failJson($isOk);

        $setting = SettingModel::select()[0];
        $setting->tb_cookie_isv = $input['cookie'];
        $setting->save();

        return $this->succJson('更新成功');
    }


    // 更新淘宝商家的cookie, 该cookie为内部使用, 用于获取旺旺名、uid、商品列表
    public function tbCookieSeller (Request $request) {

        $input = $request->put();
        $isOk = Validator::tbCookieSeller($input);
        if ($isOk !== true) return $this->failJson($isOk);

        $setting = SettingModel::select()[0];
        $setting->tb_cookie_seller = $input['cookie'];
        $setting->save();

        return $this->succJson('更新成功');
    }


    // 清0 wangwang_by_token_queryed 字段
    public function resetWangwangQueryed (Request $request) {

        $setting = SettingModel::select()[0];
        $setting->wangwang_by_token_queryed = 0;
        $setting->save();

        return $this->succJson('操作成功');
    }
}


class Validator {

    public static function tbCookieIsv ($input) {

        $cookie = @$input['cookie'];
        if ($cookie == null || !is_string($cookie)) return '参数错误';

        return true;
    }

    public static function tbCookieSeller ($input) {

        $cookie = @$input['cookie'];
        if ($cookie == null || !is_string($cookie)) return '参数错误';

        return true;
    }
}