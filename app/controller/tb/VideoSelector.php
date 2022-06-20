<?php
namespace app\controller\tb;

use think\Request;
use app\BaseController;
use think\facade\Validate;
use app\extend\tb\TbVideoList;
use app\extend\tb\TbVideoListV2;
use app\model\OldUserModel;

class VideoSelector extends BaseController {
    
    // 获取视频列表
    public function list (Request $request) {

        $input = $request->get();
        if (!VideoSelectorValidator::list($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 用户id
        $uid = $request->uid;

        $pageNum = $input['pageNum'];
        $pageSize = $input['pageSize'];
        $name = isset($input['name']) ? $input['name'] : '';
        
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;
        $result = (new TbVideoList)->handle([
            'cookie' => $cookie,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
            'name' => $name,
        ]);


        if ($result['error']) {

            return $this->resultJson([], false, $result['code_name']);
        } else {

            return $this->resultJson($result['data'], true, 'GET_SUCC');
        }
    }


    // 获取视频列表
    public function listV2 (Request $request) {

        $input = $request->get();
        if (!VideoSelectorValidator::listV2($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 用户id
        $uid = $request->uid;

        $pageNum = $input['pageNum'];
        $pageSize = $input['pageSize'];
        
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;
        $result = (new TbVideoListV2)->handle($cookie, $pageNum, $pageSize);

        
        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);


        $rows_old = $result['data']['rows'];
        $rows_new = [];
        foreach ($rows_old as $item) {
            
            array_push($rows_new, [
                // 视频id
                'id' => $item['id'],
                // 首帧
                'snapshot' => $item['snapshot'],
                // 视频宽高
                'width' => $item['width'],
                'height' => $item['height'],
                // 视频名
                'name' => $item['name'],
                // 视频秒数
                'length' => $item['length'],
                // 视频链接
                'playUrl' => '//cloud.video.taobao.com/play/u/446034741/p/1/e/6/t/1/'. $item['id'] .'.mp4'
            ]);
        }


        $result['data']['rows'] = $rows_new;
        return $this->resultJson($result['data'], true, 'GET_SUCC');
    }
}


class VideoSelectorValidator {

    public static function list ($input) {

        $validate = Validate::rule([
            'pageNum' => 'require|number|min:1',
            'pageSize' => 'require|number|min:1',
        ]);
        
        if (!$validate->check($input)) return false;
        
        // 如果有name但不为字符串
        if (isset($input['name']) && !is_string($input['name'])) return false;

        return true;
    }


    public static function listV2 ($input) {

        $validate = Validate::rule([
            'pageNum' => 'require|number|min:1',
            'pageSize' => 'require|number|min:1',
        ]);
        
        return $validate->check($input);
    }
}