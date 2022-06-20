<?php
namespace app\extend;


use think\facade\Log;

/**
 * 使用curl发送请求, 支持get、post、post上传文件
 */
class DoCurl {

    private $read_timeout;
    private $connect_timeout;

    /**
     * read_timeout 获取结果时的等待时间
     * connect_timeout 连接重试的最长等待时间
     */
    public function __construct ($option = []) {

        $this->read_timeout       = @$option['read_timeout'] ?: false;
        $this->connect_timeout    = @$option['connect_timeout'] ?: false;
        $this->cookie             = @$option['cookie'] ?: false;
        $this->auto_redirect      = @$option['auto_redirect'] ?: false;
        $this->print_header       = @$option['print_header'] ?: false;
        $this->header             = @$option['header'] ?: false;
    }


    /**
     * 初始化一个 curl 对象
     *
     * @param [String] $url
     * @return Resource curl_init()返回值
     */
    private function init ($url) {

        // 实例化
        $ch = curl_init();

        // 请求地址
        curl_setopt($ch, CURLOPT_URL, $url);
		
        // 当 HTTP 状态码大于等于 400，true 将将显示错误详情. 默认情况下将返回页面, 忽略 HTTP 代码
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        // true 将curl_exec()获取的信息以字符串返回，而不是直接输出
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 设置超时时间
        if (is_numeric($this->read_timeout)) {

			curl_setopt($ch, CURLOPT_TIMEOUT, $this->read_timeout);
		}

        // 在尝试连接时等待的秒数。设置为0, 则无限等待。
		if($this->connect_timeout){

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
		}

        // 如果是https请求, 不验证证书
		if(substr($url, 0, 5) == 'https'){

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

        // 如果有cookie, 设置cookie
        if ($this->cookie) {

            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        }

        // 是否自动化跳转
        if ($this->auto_redirect) {

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        // 是否将响应头打印出来
        if ($this->print_header) {

            curl_setopt($ch, CURLOPT_HEADER, true);
        }

        // 设置请求头
        if ($this->header) {

            // halt($this->header);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        }

        // 解决 tls 报错提示, 某些情况还是会报错, 待测试
        // curl_setopt($ch, CURLOPT_ENCODING ,'gzip');
        // curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1.2');
        // curl_setopt($ch, CURLOPT_SSL_FALSESTART, true);
        
        return $ch;
    }


    /**
     * 执行请求, 意外情况抛出错误, 正常返回结果
     *
     * @param [Resource] $ch
     * @return Exception|String 错误对象或返回值字符串
     */
    private function getRes ($ch) {

        // 发起请求
        $isOk = true;
		$reponse = curl_exec($ch);
        
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        
        // 请求失败抛出错误文字
		if ($curl_error) {

            // 记录日志
            Log::record("[DoCurl请求异常: ] 错误代码: $curl_errno; 错误提示语: $curl_error");
            $isOk = false;
		}else{

            // 非200码, 抛出错误文字和HTTP CODE
			$http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_status_code !== 200) {

                // 记录日志
                Log::record("[DoCurl请求失败: ] 状态码: $http_status_code; 返回值: $reponse");
                $isOk = false;
			}
		}

        // 关闭链接
		curl_close($ch);

		return [
            'isOk' => $isOk,
            'raw' => $reponse,
        ];
    }


    // get请求
    public function get ($url) {

        $ch = $this->init($url);
        return $this->getRes($ch);
    }


    /**
     * post请求
     *
     * @param [String] $url
     * @param array $data       请求体数据
     * @param boolean $is_json  是否以json方式发送
     * @return $this->getRes($ch)
     */
    public function post ($url, $data = [], $is_json = false) {

        $ch = $this->init($url);
        
        // 设置成post请求
        curl_setopt($ch, CURLOPT_POST, true);

        // 是否以json格式传输, 默认以multipart/form-data类型传输, CURLOPT_POSTFIELDS可以是数组或json字符串
        if ($is_json) {

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json;charset=utf-8']);
        } else {
            
            if (is_array($data)) {

                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {

                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        // 发起请求, 返回结果
        return $this->getRes($ch);
    }


    /**
     * post上传文件
     *
     * @param [String] $url
     * @param [String] $filename    文件的本地绝对地址
     * @param [String] $file_field  $_FILES中该文件显示的名称
     * @param array $data           请求体数据
     * @param array $postname       上传数据中的文件名, 默认name
     * @return $this->getRes($ch)
     */
    public function upload ($url, $filename, $file_field, $data = [], $postname = null) {

        // 实例化
        $ch = $this->init($url);

        // 设置为上传场景
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

        // 默认值, 不手动设置也是这个值
        $mimetype = 'application/octet-stream';

        // 上传数据中的文件名称, 默认为name
        $postname = is_string($postname) ? $postname : time();
        $postname = strval($postname);

        // 设置上传数据
        $post_data = [];
        $post_data[$file_field] = new \CURLFile($filename, $mimetype, $postname);

        // 追加其他额外数据
        $post_data = array_merge($post_data, $data);

        // 设置请求体数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        // 发起请求, 返回结果
        return $this->getRes($ch);
    }
}