<?php
namespace app\extend\tb;

use app\extend\tb\TbUtil;
use app\extend\Util;
use app\extend\DoCurl;
use app\extend\RecordError;

/**
 * 获取视频列表
 * 使用的接口: https://h5api.m.taobao.com/h5/mtop.taobao.guangguang.material.get/1.0/
 * 当前(2021-12-29)的返回格式是:
    [
        'api' => 'mtop.taobao.guangguang.material.get',
        'data' => [
            'fail' => 'false',
            'model' => [xxxx],
        ],
        'ret' => ['SUCCESS::调用成功'],
        'v' => '1.0'
    ]
 * 该接口要求打开此页面后才能使用 https://sucai.wangpu.taobao.com/videoSelector.htm?appKey=7596&hideHeader=false&type=video&maxDuration=120&validAspectRatio=(4:3,16:9)&handleId=callback1&publishType=normal&bizCode=seller_normal_vod_publish&videoOptions=4&scene=wangpu_detail_videomodule&switchAccount=2&from=pc_wangpu_detailvideo#/
 * 打开此页面后, 再去获取cookie, 否则会提示token过期
 */
class TbVideoList {

    // 判断淘宝返回数据是否可用
    private function isApiOk ($raw, $jsonp_callback_name) {

        // 尝试转成数组, 无法转换成数组则退出
        $input = Util::jsonpToArray($raw, $jsonp_callback_name);
        if ($input == null) return 'SYSTEM_ERROR';

        // 通过ret字段和data.fail字段判断接口是否正常, 没有该字段, 无法判断
        try {
            
            $ret = $input['ret'][0];
            $fail = $input['data']['fail'];
        } catch (\Throwable $th) {

            $ret = null;
            $fail = null;
        }
        
        if ($ret == null || $fail == null) return 'SYSTEM_ERROR';

        // 这两个字段返回其他值, 调用失败
        if ($ret !== 'SUCCESS::调用成功' || $fail !== 'false') return 'SYSTEM_ERROR';

        return true;
    }


    // 生成签名
    private function buildSign ($_m_h5_tk, $time, $app_key, $data) {

        return md5(implode('&', [
            $_m_h5_tk,
            $time,
            $app_key,
            $data,
        ]));
    }


    // 入口
    public function handle ($param) {

        [
            'cookie' => $cookie,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
            'name' => $name,
        ] = $param;

        $url = 'https://h5api.m.taobao.com/h5/mtop.taobao.guangguang.material.get/1.0/';
        $_m_h5_tk = TbUtil::getTbMH5TK($cookie);


        $app_key = '12574478';
        $time = time() . '999';
        $payload = json_encode([
            // 固定
            'appKey' => '7596',
            'hideInvalidVideo' => false,
            'type' => "mobile",

            // 动态
            'keyWord' => $name,
            'name' => $name,
            'pageNum' => intval($pageNum),
            'pageSize' => intval($pageSize),
        ]);
        $sign = $this->buildSign($_m_h5_tk, $time, $app_key, $payload);

        // 参数
        $jsonp_callback_name = 'mtopjsonp1';
        $query_arr = [
            // 固定
            'jsv' => '2.6.1',
            'appKey' => $app_key,
            'api' => 'mtop.taobao.guangguang.material.get',
            'v' => '1.0',
            'type' => 'jsonp',
            'callback' => $jsonp_callback_name,
            'dataType' => 'jsonp',

            // 动态
            't' => $time,
            'sign' => $sign,
            'data' => $payload,
        ];
        $query = Util::arrayToQueryString($query_arr);
        $url = "$url?$query";

        // 发起请求
        $doCurl = new DoCurl(['cookie' => $cookie]);
        $response = $doCurl->get($url);
        $isOk = $response['isOk'];
        $raw = $response['raw'];

        // 请求有问题, 提前退出
        if (!$isOk) {

            RecordError::handle(
                __CLASS__,
                "链接($url)",
                '淘宝接口http请求错误'
            );
            return Util::formatResult([], false, 'SYSTEM_ERROR');
        }

        
        // 淘宝接口返回异常, 提前返回
        $err_name = $this->isApiOk($raw, $jsonp_callback_name);
        if ($err_name !== true) {

            RecordError::handle(
                __CLASS__,
                '数据: "' . $raw .'"',
                '淘宝返回数据异常'
            );
            return Util::formatResult([], false, $err_name);
        }

        $raw = Util::jsonpToArray($raw, $jsonp_callback_name);
        try {
            
            $result_data = $raw['data']['model'];

        } catch (\Throwable $th) {

            RecordError::handle(
                __CLASS__,
                '数据: "'. json_encode($raw) .'"',
                '淘宝接口返回数据格式改变'
            );
            $result_data = null;
        }

        // 获取不到值, 返回错误
        if ($result_data == null) return Util::formatResult([], false, 'SYSTEM_ERROR');

        // 返回
        return Util::formatResult($result_data, true);
    }
}