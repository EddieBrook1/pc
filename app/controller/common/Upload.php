<?php
namespace app\controller\common;

use think\Request;
use app\BaseController;
use app\extend\upload\UploadFile;
use think\facade\Validate;

class Upload extends BaseController {


    public function uploadFile (Request $request) {

        $input = $request->post();
        if (!UploadValidator::uploadFile($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 上传
        $upload = new UploadFile();
        $formatResult = $upload->upload($input);

        return json($formatResult);
    }
}



class UploadValidator {

    public static function uploadFile ($input) {

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
}