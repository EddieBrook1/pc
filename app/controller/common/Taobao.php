<?php
namespace app\controller\common;

use think\Request;
use think\facade\Validate;
use app\BaseController;

use app\custom\taobao\TBGoodsList;
use app\custom\taobao\TBUserList;
use app\custom\taobao\TBGetWangwangByToken;
use app\custom\taobao\TBReplaceMainPic;

use app\custom\DoCurl;

class Taobao extends BaseController {

    private const SHOP_LIST_FIELD_KEEP = [
        
        // 旺旺uid
        'uid',
        // 旺旺名
        'nick',
        // 店铺地址
        'shopUrl',
        // 店铺logo
        'picUrl',
        // 当前是否为要查找的店铺
        'isSearchNick',
    ];
    

    // 通过旺旺名获取商品列表
    public function getGoodsListByName (Request $request) {

        // 验证入参
        $input = $request->get();
        $isOk = Validator::getGoodsListByName($input);
        if ($isOk !== true) return $this->failJson($isOk);
        
        [
            'wangwang_name' => $wangwang_name,
            'page' => $page,
            'size' => $size,
        ] = $input;

        // 生成商品列表
        $taobaoGoodsList = new TBGoodsList;
        $goods_list = $taobaoGoodsList->handle($wangwang_name, $page, $size);
        if (!$goods_list) return $this->failJson('获取失败');

        // 返回
        return $goods_list
            ? $this->succJson('获取成功', $goods_list)
            : $this->failJson('获取失败');
    }


    // 通过旺旺名获取店铺列表
    public function getShopListByName (Request $request) {

        // 验证入参
        $input = $request->get();
        $isOk = Validator::getShopListByName($input);
        if ($isOk !== true) return $this->failJson($isOk);

        [
            'wangwang_name' => $wangwang_name,
            'page' => $page,
            'size' => $size,
        ] = $input;

        // 生成商品列表
        $tBUserList = new TBUserList;
        $user_list = $tBUserList->handle($wangwang_name, $page, $size);
        if (!$user_list) return $this->failJson('获取失败');

        // 过滤掉部分字段
        $filtered_data = [];
        foreach ($user_list['data'] as $item) {

            $cur = [];
            foreach (self::SHOP_LIST_FIELD_KEEP as $field) {
                
                $cur[$field] = isset($item[$field]) ? $item[$field] : false;
            }

            array_push($filtered_data, $cur);
        }
        $user_list['data'] = $filtered_data;


        // 返回
        return $this->succJson('获取成功', $user_list);
    }


    // 通过access_token获取淘宝用户旺旺名
    public function getWangwangByToken (Request $request) {

        $input = $request->get();
        $isOk = Validator::getWangwangByToken($input);
        if ($isOk !== true) return $this->failJson($isOk);

        $ins = new TBGetWangwangByToken;
        $wangwang_name = $ins->handle($input['access_token']);

        return $wangwang_name
            ? $this->succJson('获取成功', [
                'wangwang_name' => $wangwang_name
            ])
            : $this->failJson('获取失败');
    }


    // 改变商品主图
    public function replaceMainPic (Request $request) {

        $input = $request->post();
        $isOk = Validator::replaceMainPic($input);
        if ($isOk !== true) return $this->failJson('更换失败, 参数错误');

        $tBReplaceMainPic = new TBReplaceMainPic;
        $isOk = $tBReplaceMainPic->handle($input);

        return $isOk === true
            ? $this->succJson('更换成功')
            : $this->failJson($isOk);
    }
}


// 验证器
class Validator {

    public static function getGoodsListByName ($input) {

        $validate = Validate::rule([
            'page' => 'require|number|egt:1',
            'size' => 'require|number|egt:1',
            'wangwang_name' => 'require',
        ]);

        if (!$validate->check($input)) return $validate->getError();

        return true;
    }


    public static function getShopListByName ($input) {

        $validate = Validate::rule([
            'page' => 'require|number|egt:1',
            'size' => 'require|number|egt:1',
            'wangwang_name' => 'require',
        ]);

        if (!$validate->check($input)) return $validate->getError();

        return true;
    }


    public static function getWangwangByToken ($input) {

        $access_token = @$input['access_token'];
        if ($access_token == null) return '参数错误';
        if (!is_string($access_token)) return '参数错误';

        return true;
    }


    public static function replaceMainPic ($input) {
        
        $validate = Validate::rule([
            'user_id' => 'require|number',
            'goods_id' => 'require|number',
            'img_urls' => 'require|array_str',
        ]);

        if (!$validate->check($input)) return $validate->getError();

        return true;
    }
}