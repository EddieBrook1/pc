<?php
namespace app\extend\pdd;

use think\facade\Log;
use app\extend\DoCurl;

class PddSdk {

    public const API_ERR_CODE = -1;         // api名称错误
    public const REQUEST_ERR_CODE = -2;     // 请求失败

    private $api_base_url;
    private $data_type;
    private $client_id;
    private $secret_key;

    public function __construct ($option) {

        $this->api_base_url = $option['api_base_url'];
        $this->data_type = $option['data_type'];
        $this->client_id = $option['client_id'];
        $this->secret_key = $option['secret_key'];
    }


    private static function recordErr ($msg, $code) {

        Log::record(json_encode(compact('msg', 'code')));
    }


    // 验证签名方法
	public function sign ($arr) {

        //将数组依据键名/字段名排序, 规则：ascii码；
		ksort($arr);

        //遍历拼接
        $str = '';
        foreach ($arr as $key => $value) {
            $str = $str . $key . $value;
        }

        // 首尾拼接client_secret
        $str = $this->secret_key.$str.$this->secret_key;

        // MD5加密
        $str = MD5($str);

        //大写转化
        return strtoupper($str);
	}

    
    public function exec ($access_token, $api_name, $raw_data) {

        // 是否有对应的入参处理方法存在, 不存在则为接口错误
        $fn_name = str_replace('.', '_', $api_name);
        if (!method_exists($this, $fn_name)) return false;

        // 处理入参, 返回可使用的参数
        $data = $this->$fn_name($raw_data);

        // 加入公共参数
        $data['client_id'] = $this->client_id;
        $data['timestamp'] = time();
        $data['data_type'] = $this->data_type;
        $data['access_token'] = $access_token;

        // 算签名, 把签名加进去
        $sign = $this->sign($data);
        $data['sign'] = $sign;
        
        // 请求
        $result = null;
        try {

            $doCurl = new DoCurl;
            $result = $doCurl->post($this->api_base_url, $data);
        } catch (\Exception $e) {

            $result = null;
            $err_msg = $e->getMessage();
            $err_code = $e->getCode();

            // 记录日志
            self::recordErr($err_msg, $err_code);
        }

        return $result == null
            ? false
            : json_decode($result, true); 
    }


    // 图片上传-base64码
    private function pdd_goods_image_upload ($raw_data) {

        $raw_data['type'] = 'pdd.goods.image.upload';
        return $raw_data;
    }
}