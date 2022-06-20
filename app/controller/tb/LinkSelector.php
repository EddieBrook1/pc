<?php
namespace app\controller\tb;

use think\Request;
use app\BaseController;
use think\facade\Validate;

use app\extend\tb\TbLinkSelectorCommonLink;
use app\extend\tb\TbLinkSelectorCategoryLink;
use app\extend\tb\TbLinkSelectorCouponLink;
use app\model\OldUserModel;

class LinkSelector extends BaseController {

    // 常用链接
    public function commonLink (Request $request) {

        // 用户id
        $uid = $request->uid;
        
        // 客户cookiie
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 请求数据
        $result = (new TbLinkSelectorCommonLink)->handle($cookie);

        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);

        // 取出该用户的旺旺名
        $wangwang = OldUserModel::field('password')
            ->where('user_id', $uid)
            ->find()
            ->password;

        // 拼接旺旺客服地址
        $wangwang_link = "https://h5.m.taobao.com/ww/index.htm#!dialog-$wangwang";
        array_push($result['data'], [
            'link' => $wangwang_link,
            'description' => '旺旺客服',
            'recommend' => false,
            'title' => '旺旺客服',
        ]);

        return $this->resultJson($result['data'], true, 'GET_SUCC');
    }


    // 宝贝分类
    public function categoryLink (Request $request) {

        $input = $request->get();
        $isOk = LinkSelectorValidator::categoryLink($input);
        if (!$isOk) return $this->resultJson([], false, 'PARAM_ERR');

        // 解构
        $currentPage = isset($input['currentPage']) ? $input['currentPage'] : 1;
        $pageSize = isset($input['pageSize']) ? $input['pageSize'] : 20;

        // 用户id
        $uid = $request->uid;
        
        // 客户cookie
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 请求数据
        $result = (new TbLinkSelectorCategoryLink)->handle($cookie, $currentPage, $pageSize);

        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);

        return $this->resultJson($result['data'], true, 'GET_SUCC');

    }


    // 优惠券
    public function couponLink (Request $request) {

        $input = $request->get();
        $isOk = LinkSelectorValidator::couponLink($input);
        if (!$isOk) return $this->resultJson([], false, 'PARAM_ERR');

        // 解构
        $currentPage = isset($input['currentPage']) ? $input['currentPage'] : 1;
        $pageSize = isset($input['pageSize']) ? $input['pageSize'] : 20;


        // 用户id
        $uid = $request->uid;
        
        // 客户cookie
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 请求数据
        $result = (new TbLinkSelectorCouponLink)->handle($cookie, $currentPage, $pageSize);

        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);

        return $this->resultJson($result['data'], true, 'GET_SUCC');
    }
}


class LinkSelectorValidator {

    public static function categoryLink ($input) {

        $validate = Validate::rule([
            'currentPage' => 'number',
            'pageSize' => 'number',
        ]);

        return $validate->check($input);
    }


    public static function couponLink ($input) {

        $validate = Validate::rule([
            'currentPage' => 'number',
            'pageSize' => 'number',
        ]);

        return $validate->check($input);
    }
}