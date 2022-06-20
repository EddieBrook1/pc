<?php
namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\TestUserModel;
use think\facade\Validate;

class TestUser extends BaseController {

    public function list (Request $request) {

        $input = $request->get();
        $page = @intval($input['page']) ?: 1;
        $size = @intval($input['size']) ?: 10;
        $page = is_numeric($page) ? $page : 1;
        $size = is_numeric($size) ? $size : 10;

        $data = TestUserModel::order('create_time', 'desc')
            ->page($page, $size)
            ->select()
            ->toArray();

        $current_page = $page;
        $size = $size;
        $total = TestUserModel::count();
        $total_page = intval(ceil($total / $size));

        return $this->resultJson([
            'current_page' => $current_page,
            'data' => $data,
            'size' => $size,
            'total' => $total,
            'total_page' => $total_page,
        ], true, 'EXEC_SUCC');
    }


    public function add (Request $request) {

        $input = $request->post();
        $validate = Validate::rule([
            'name' => 'require|alphaNum',
            'age' => 'require|number',
        ]);
        if (!$validate->check($input)) {

            return $this->resultJson([
                'msg_detail' => $validate->getError()
            ], false, 'PARAM_ERR');
        }
        

        $testUser = TestUserModel::create([
            'name' => $input['name'],
            'age' => $input['age']
        ]);
        

        return $this->resultJson($testUser->toArray(), true, 'ADD_SUCC');
    }


    public function update (Request $request) {

        $input = $request->post();
        $validate = Validate::rule([
            'name' => 'require|alphaNum',
            'id' => 'require|number',
        ]);
        if (!$validate->check($input)) {

            return $this->resultJson([
                'msg_detail' => $validate->getError()
            ], false, 'PARAM_ERR');
        }


        $testUser = TestUserModel::find($input['id']);

        if ($testUser == null) {

            return $this->resultJson([
                'msg_detail' => '用户不存在'
            ], false, 'PARAM_ERR');
        }


        $testUser->name = $input['name'];
        $testUser->save();


        return $this->resultJson([], true, 'UPDATE_SUCC');
    }


    public function del (Request $request) {

        $input = $request->post();
        $validate = Validate::rule([
            'id' => 'require|number',
        ]);
        if (!$validate->check($input)) {

            return $this->resultJson([
                'msg_detail' => $validate->getError()
            ], false, 'PARAM_ERR');
        }


        $testUser = TestUserModel::find($input['id']);
        if ($testUser) {

            $testUser->delete();
        }
        
        return $this->resultJson([], true, 'EXEC_SUCC');
    }
}