<?php
namespace app\extend\tb;

use app\extend\tb\TbGetFolder;

// 在处理淘宝接口时发现的一些可以通用的方法
class TbUtil {


    /** 从cookie中提取  _tb_token
     *
     * @param [String] $cookie 客户cookie
     * @return null|String  失败返回null, 成功返回字符串
     */
    public static function getTbToken ($cookie) {

        $arr = explode('; ', $cookie);
        $res = null;
        foreach ($arr as $item) {
            
            preg_match('/_tb_token_=(.*)/', $item, $p);
            if (!empty($p)) {

                $res = $p[1];
                break;
            }
        }

        return $res;
    }


    /** 从cookie中提取  _m_h5_tk
     *
     * @param [String] $cookie 客户cookie
     * @return null|String  失败返回null, 成功返回字符串
     */
    public static function getTbMH5TK ($cookie) {

        $arr = explode('; ', $cookie);
        $res = null;
        foreach ($arr as $item) {
            
            preg_match('/_m_h5_tk=(.*)/', $item, $p);
            if (!empty($p)) {

                $res = $p[1];
                break;
            }
        }

        $exploded = explode('_', $res);
        return array_shift($exploded);
    }


    /** 获取顶层文件夹下名为千绘的文件夹的id, 如果不存在创建一个并返回id
     *
     * @param [String] $cookie 用户cookie
     * @return Number|String  成功返回文件夹id, 失败返回错误代码名称
     */
    public static function getQianhuiDirIdOrBuild ($cookie) {

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


    /** 判断客户的cookie还能不能用
     * 利用TbGetFolder方法, 该方法不会触发淘宝全部登出的副作用
     * 
     * @param [String] $cookie
     * @return boolean
     */
    public static function isCookieValid ($cookie) {

        $result = (new TbGetFolder)->handle($cookie);
        return !$result['error'];
    }
}