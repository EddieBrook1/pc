<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use app\extend\Util;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    private const SUCC_CODE = 1;    // 默认的成功代码
    private const ERR_CODE = -1;    // 默认的失败代码

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }


    // 成功的json返回值
    protected function succJson ($msg = '操作成功', $data = [], $code = self::SUCC_CODE) {

        return json(\compact('msg', 'data', 'code'));
    }


    // 失败的json返回值
    protected function failJson ($err_name) {

        $code = config("code.$err_name");
        if ($code) {

            return json([
                'msg' => $code['msg'],
                'code' => $code['code'],
                'data' => [],
            ]);
        } else {

            return json([
                'msg' => '操作失败',
                'code' => -1,
                'data' => [],
            ]);
        }
    }


    // 通用的json返回值
    protected function resultJson ($data = [], $isOk, $err_name = 'SYSTEM_ERR', $msg = '') {

        $result = Util::formatResult($data, $isOk, $err_name, $msg);
        return json($result);
    }


    // 上传用的json返回值, done表示该文件全部分片都已经上传完成且文件可用, success表示此次分片是否上传成功
    protected function uploadResultJson ($data = [], $isOk, $err_name = 'SYSTEM_ERR', $done = false, $success = true) {

        $result = Util::formatResult($data, $isOk, $err_name);
        $result['done'] = $done;
        $result['success'] = $success;
        return json($result);
    }
}
