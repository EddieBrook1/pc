<?php
namespace app\controller\tb;

use think\Request;
use app\BaseController;
use think\facade\Validate;
use app\extend\tb\TbItemList;
use app\extend\tb\TbCommonLink;
use app\model\OldUserModel;

class Goods extends BaseController {

    // 商品列表
    public function list (Request $request) {

        $input = $request->get();
        if (!GoodsValidator::list($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 解构
        $currentPage = $input['currentPage'];
        $pageSize = $input['pageSize'];
        $online = isset($input['online']) ? $input['online'] : null;
        $q = isset($input['q']) ? $input['q'] : null;
        
        // 用户id
        $uid = $request->uid;
        
        // 客户cookiie
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 请求数据
        $result = (new TbItemList)->handle([
            'cookie' => $cookie,
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
            'online' => $online,
            'q' => $q,
        ]);

        
        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);
        

        $itemList_old = $result['data']['itemList'];
        $itemList_new = [];
        foreach ($itemList_old as $item) {

            $itemView_now = $item['itemView'];
            array_push($itemList_new, [
                'itemId' => $itemView_now['itemId'],
                'itemName' => $itemView_now['itemName'],
                'fullLogoUrl' => $itemView_now['fullLogoUrl'],
                'price' => $itemView_now['price'],
                'soldQuantity' => $itemView_now['soldQuantity'],
                'itemDetailUrl' => $itemView_now['itemDetailUrl'],
            ]);
        }

        
        $result['data']['itemList'] = $itemList_new;
        return $this->resultJson($result['data'], true, 'GET_SUCC');
    }


    // 常用链接
    public function commonLink (Request $request) {

        // 用户id
        $uid = $request->uid;
        
        // 客户cookiie
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 请求数据
        $result = (new TbCommonLink)->handle($cookie);

        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);

        return $this->resultJson($result['data'], true, 'GET_SUCC');
    }
}


// 入参验证器
class GoodsValidator {

    public static function list ($input) {

        $validate = Validate::rule([
            'currentPage' => 'require|number',
            'pageSize' => 'require|number',
            'online' => 'number|in:1,2',
        ]);

        return $validate->check($input);
    }
}