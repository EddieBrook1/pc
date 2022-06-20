<?php
namespace app\controller\tb;

use think\Request;
use think\facade\Validate;
use app\BaseController;

use app\model\OldUserModel;
use app\model\UploadModel;

use app\extend\tb\TbSucaiDirTree;
use app\extend\tb\TbSucaiDirContent;
use app\extend\upload\UploadFile;
use app\extend\tb\TbStreamUpload;
use app\extend\tb\TbSucaiAddDir;
use app\extend\tb\TbSucaiFiles;

class Sucai extends BaseController {

    // 获取文件夹树
    public function dirTree (Request $request) {

        $input = $request->get();
        
        // 用户id
        $uid = $request->uid;

        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;
        $result = (new TbSucaiDirTree)->handle($cookie);

        if ($result['error']) {

            return $this->resultJson([], false, $result['code_name']);
        } else {

            return $this->resultJson($result['data'], true, 'GET_SUCC');
        }
    }


    // 获取某个文件夹下的图片
    public function list (Request $request) {

        // 验证入参
        $input = $request->get();
        if (!SucaiValidator::list($input)) return $this->resultJson([], false, 'PARAM_ERR');
        
        // 用户id
        $uid = $request->uid;

        $cat_id = $input['cat_id'];
        $page = $input['page'];
        
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;
        $result = (new TbSucaiDirContent)->handle($cookie, $cat_id, $page);

        if ($result['error']) {

            return $this->resultJson([], false, $result['code_name']);
        } else {

            return $this->resultJson($result['data'], true, 'GET_SUCC');
        }
    }


    // 获取某个文件夹下的图片, v2, 该接口可以控制每页返回几条数据
    public function listV2 (Request $request) {

        // 验证入参
        $input = $request->get();
        if (!SucaiValidator::listV2($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 用户id
        $uid = $request->uid;

        $folderId = $input['folderId'];
        $page = $input['page'];
        $pageSize = $input['pageSize'];

        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;
        $result = (new TbSucaiFiles)->handle([
            'cookie' => $cookie,
            'page' => $page,
            'pageSize' => $pageSize,
            'folderId' => $folderId,
        ]);

        if ($result['error']) {

            return $this->resultJson([], false, $result['code_name']);
        } else {

            return $this->resultJson($result['data'], true, 'GET_SUCC');
        }
    }


    /**
     * 获取顶层文件夹下名为千绘的文件夹的id, 如果不存在创建一个并返回id
     *
     * @param [String] $cookie 用户cookie
     * @return Number|String  成功返回文件夹id, 失败返回错误代码名称
     */
    private static function getQianhuiDirIdOrBuild ($cookie) {

        $qianhui_dirname = '千绘';

        // 1. 取出文件夹树, 看有没有这个文件夹
        $dir_tree = (new TbSucaiDirTree)->handle($cookie);

        // 文件夹树获取失败, 返回
        if ($dir_tree['error']) return false;

        // 第一层文件夹下的数据
        $first_folder = $dir_tree['data']['dirs']['children'];

        // 遍历判断
        $folder_id = null;
        foreach ($first_folder as $item) {
            
            if ($item['name'] == $qianhui_dirname) {

                $folder_id = $item['id'];
                break;
            }
        }

        
        // 文件夹id存在, 返回
        if ($folder_id) return $folder_id;


        // 2. 不存在, 创建一个

        // 在哪个文件夹下新建 $qianhui_dirname 文件夹, 0为顶层文件夹
        $dir_id = '0';
        $add_dir_result = (new TbSucaiAddDir)->handle($cookie, $dir_id, $qianhui_dirname);

        // 新建文件夹失败, 返回
        if ($add_dir_result['error']) return false;
        
        // 取出文件夹id
        $folder_id = $add_dir_result['data']['id'];
        return $folder_id;
    }


    // 上传图片
    public function upload (Request $request) {

        $input = $request->post();
        if (!SucaiValidator::upload($input)) return $this->uploadResultJson(
            [],             // data
            false,          // error
            'PARAM_ERR',    // code_name
            false,          // done
            false           // success
        );


        // 上传
        $upload = new UploadFile();
        $upload_result = $upload->upload($input);

        // 上传错误, 退出
        if ($upload_result['error']) return $this->uploadResultJson(
            [],                             // data
            false,                          // error
            $upload_result['code_name'],    // code_name
            false,                          // done
            false                           // success
        );

        // 正在上传分片, 退出
        if (!$upload_result['done']) return $this->uploadResultJson(
            [],                             // data
            true,                           // error
            $upload_result['code_name'],    // code_name
            false,                          // done
            true                            // success
        );


        // ---文件上传完成
        
        // 文件本地数据库的id
        $local_id = $upload_result['data']['id'];

        // 1. 取出客户cookie
        // 用户id
        $uid = $request->uid;
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        // 2. 千绘文件夹的id
        $folder_id = self::getQianhuiDirIdOrBuild($cookie);

        // 千绘文件夹id获取失败
        if ($folder_id === false) return $this->uploadResultJson(
            [],             // data
            false,          // error
            'SYSTEM_ERROR', // code_name
            false,          // done
            false           // success
        );

        // 3. 图片的本地地址
        $pic_path = UploadModel::find($local_id)->local_path;

        // 4. 提交
        $commit_result = (new TbStreamUpload)->handle([
            'cookie' => $cookie,
            'pic_path' => $pic_path,
            'folder_id' => $folder_id,
        ]);


        if ($commit_result['error']) {
            
            // 错误退出
            return $this->uploadResultJson(
                [],                             // data
                false,                          // error
                $commit_result['code_name'],    // code_name
                false,                          // done
                false                           // success
            );
        } else {

            // 上传成功
            return $this->uploadResultJson(
                $commit_result['data'],     // data
                true,                       // error
                'UPLOAD_SUCC',              // code_name
                true,                       // done
                true                        // success
            );
        }
    }


    // 新建文件夹
    public function addDir (Request $request) {

        // 验证入参
        $input = $request->post();
        if (!SucaiValidator::addDir($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 用户id
        $uid = $request->uid;
        $dir_id = $input['dir_id'];
        $name = $input['name'];

        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;
        $result = (new TbSucaiAddDir)->handle($cookie, $dir_id, $name);


        if ($result['error']) {

            return $this->resultJson([], false, $result['code_name']);
        } else {

            return $this->resultJson($result['data'], true, 'ADD_SUCC');
        }
    }
}


// 入参验证器
class SucaiValidator {

    public static function list ($input) {

        $validate = Validate::rule([
            'page' => 'require|number',
            'cat_id' => 'require|number',
        ]);
        return $validate->check($input);
    }


    public static function listV2 ($input) {

        $validate = Validate::rule([
            'page' => 'require|number|min:1',
            'pageSize' => 'require|number|min:1',
            'folderId' => 'require|number',
        ]);
        return $validate->check($input);
    }
    

    public static function upload ($input) {

        $validate = Validate::rule([

            // fineupload插件自带的参数
            'qquuid' => 'require',
            'qqpartindex' => 'require',
            'qqtotalparts' => 'require',
            'qqfilename' => 'require',

            // 其他自定义的参数, 以这个文件名为准, 当使用uploader.addFile添加Blob对象时, qqfilename无法正确被设置
            'real_filename' => 'require',
        ]);

        return $validate->check($input);
    }


    public static function addDir ($input) {

        $validate = Validate::rule([
            'dir_id' => 'require|number',
            'name' => 'require|chsDash'
        ]);
        return $validate->check($input);
    }
}