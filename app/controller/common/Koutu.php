<?php
namespace app\controller\common;

use think\Request;
use think\facade\Log;
use app\BaseController;

/**
 * 如报以下错误请修改该文件: vendor/alibabacloud/tea-oss-utils/src/VerifyStream.php/VerifyStream类的read方法, 在该方法后加 : string 改成强类型声明
 * Declaration of AlibabaCloud\Tea\OSSUtils\VerifyStream::read($length) must be compatible with GuzzleHttp\Psr7\Stream::read($length): string
 */
use AlibabaCloud\SDK\ViapiUtils\ViapiUtils;

// 调用抠图接口需要
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

use app\qianhui\Util;
use app\qianhui\sdk\PddSdk;
use app\qianhui\Upload as QianhuiUpload;

use app\model\OldPuserModel;
use app\model\KoutuModel;
use app\model\KoutuExampleModel;

class Koutu extends BaseController {

    private const SUCC_CODE = 1;    // 默认的成功代码
    private const ERR_CODE = -1;    // 默认的失败代码

    // 重载成功的json返回值
    protected function succJson ($msg = '操作成功', $data = [], $code = self::SUCC_CODE) {

        $success = true;
        return json(\compact('success', 'msg', 'data', 'code'));
    }


    // 重置失败的json返回值
    protected function failJson ($msg = '操作失败', $data = [], $code = self::ERR_CODE) {

        $success = false;
        return json(\compact('success', 'msg', 'data', 'code'));
    }


    /**
     * 生成阿里云OSS的链接, OSS是阿里云的云存储服务, 按量收费
     * 当前方法使用的是它们的调试接口, 在高峰期可能会生成失败
     *
     * @param [String] $url 图片本地地址或网络地址
     * @return String|false   成功返回阿里云链接失败返回false, 生成失败会记录日志
     */
    private static function buildOSSUrl ($file_url) {

        $key_id = config('alipay.zdx_key_id');         // 阿里云key
        $key_secret = config('alipay.zdx_key_secret'); // 阿里云secret

        // 上传成功后, 返回上传后的文件地址, 失败无返回值
        $oss_url = ViapiUtils::upload($key_id, $key_secret, $file_url);

        Log::record("生成oss地址失败, 原地址: $file_url");
        return empty($oss_url)
            ? false
            : $oss_url;
    }


    /**
     * 调用阿里云抠图接口把图片转换成png
     *
     * @param String $oss_url   阿里云OSS链接
     * @return String|false     成功返回png链接, 失败返回false, 失败会记录日志
     */
    private static function convert ($oss_url) {

        $key_id = config('alipay.zdx_key_id');         // 阿里云key
        $key_secret = config('alipay.zdx_key_secret'); // 阿里云secret


        AlibabaCloud::accessKeyClient($key_id, $key_secret)
            ->regionId('cn-shanghai')
            ->asDefaultClient();

        try {
            
            $result = AlibabaCloud::rpc()
                ->product('imageseg')
                ->version('2019-12-30')
                ->action('SegmentCommonImage')
                ->method('POST')
                ->host('imageseg.cn-shanghai.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-shanghai",
                        'ImageURL' => $oss_url,
                    ],
                ])
                ->request();

            return $result->toArray()['Data']['ImageURL'];
        } catch (ClientException $e) {

            Log::record($e->getErrorMessage());
        } catch (ServerException $e) {
            
            Log::record($e->getErrorMessage());
        }

        return false;
    }


    // 调用抠图接口
    private function koutu ($ori_url) {

        // 生成OSS链接
        $oss_url = self::buildOSSUrl($ori_url);
        if (!$oss_url) return false;

        // 调用抠图接口
        $result_url = self::convert($oss_url);
        if (!$result_url) return false;

        return $result_url;
    }


    // 上传图片到拼多多空间, 成功返回url, 失败返回false
    private function uploadToPdd ($local_path) {

        // 拼多多sdk
        $pddSdk = new PddSdk([
            'api_base_url' => config('pdd.api_base_url'),
            'data_type' => 'JSON',
            'client_id' => config('pdd.sj_client_id'),
            'secret_key' => config('pdd.sj_secret_key'),
        ]);

        // 获取千知鱼账号的access_token
        $tupian_user_id = config('pdd.tupian_user_id');
        $access_token = OldPuserModel::find($tupian_user_id)->toArray()['access_token'];

        // 图片转成64码, 执行上传动作
        $data = [ 'image' => Util::base64EncodeImage($local_path) ];
        $res = $pddSdk->exec(
            $access_token,
            'pdd.goods.image.upload',
            $data
        );

        // 成功返回url, 失败返回false
        if (!isset($res['error_response'])) {

            return $res['goods_image_upload_response']['image_url'];
        } else {

            // 记录日志
            Log::record('图片上传到拼多多图片空间失败: ' . json_encode($res, 320));
            return false;
        }
    }


    // 抠图成功后的返回值
    private function koutuSuccRes ($koutu) {

        return [
            'id' => $koutu->id,
            'origin_url' => $koutu->origin_url,
            'after_url' => $koutu->after_url,
            'download_url' => 'http://test.qianzhiyu.net/koutu/download?id=' . $koutu->id,
        ];
    }


    // 判断购买是否到期
    private function isExpire () {

        return false;
    }


    // 上传式抠图
    public function upload (Request $request) {

        // 判断服务是否到期
        if ($this->isExpire()) {

            $code = config('code')['SERVICE_EXPIRES'];
            return $this->failJson($code['msg'], [], $code['code']);
        }


        $file_field = 'qqfile';     // 上传来的文件下标名称
        $upload = new QianhuiUpload($file_field);

        // 分片是否上传成功
        $isOk = $upload->upload($request);
        if ($isOk !== true) return $this->failJson('上传失败');

        // 上传是否完成, 未完成返回成功提示继续上传
        if (!$upload->is_complate) return $this->succJson('上传成功');

        // 上传完成得到地址, 再传入拼多多接口
        $uploaded_path = $upload->uploaded_path;

        // 根据md5判断这个图片是否之前调用过切图接口
        $origin_md5 = md5_file($uploaded_path);
        $db_record = KoutuModel::where('md5', $origin_md5)->find();
        if ($db_record != null) {

            // 之前有扣过图, 删掉原片
            unlink($uploaded_path);

            // 返回
            return $this->succJson('抠图成功', $this->koutuSuccRes($db_record));
        }

        // 上传到图片空间
        $before_pdd_url = $this->uploadToPdd($uploaded_path);
        if ($before_pdd_url === false) return $this->failJson('上传失败');
        // 上传成功, 删掉原片
        unlink($uploaded_path);

        // 调用抠图接口
        $after_alipay_url = $this->koutu($before_pdd_url);
        if ($after_alipay_url === false) return $this->failJson('抠图失败');
        
        // 把抠图后的阿里云图片地址传到拼多多图片空间
        $after_pdd_url = $this->uploadToPdd($after_alipay_url);
        if ($after_pdd_url === false) return $this->failJson('上传失败');

        // 入库
        $created = KoutuModel::create([
            'origin_url' => $before_pdd_url,
            'after_url' => $after_pdd_url,
            'md5' => $origin_md5,
        ]);

        // 返回
        return $this->succJson('抠图成功', $this->koutuSuccRes($created));
    }


    // 下载抠图后的图片
    public function downloadAfterImg (Request $request) {

        $id = $request->get()['id'];
        $koutu = KoutuModel::find($id);

        if ($koutu == null) {

            return $this->failJson('下载失败, 图片不存在');
        } else {

            // 拉取图片到本地再下载
            $data = file_get_contents($koutu->after_url);
            return download($data, '图片.png', true);
        }
    }
}
