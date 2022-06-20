<?php
namespace app\extend;

use app\model\SettingModel;

class Setting {

    private $setting;

    public function __construct () {

        $this->setting = SettingModel::select()[0];
    }


    /**
     * 获取淘宝同步详情时显示在旺铺编辑器里的头图
     *
     * @return Array 形如:
        [
            'url' => 'xxx',
            'width' => xxx,
            'height' => xxx
        ]
     * 
     */
    public function getAdPic1 () {

        return json_decode($this->setting->ad_pic_1, true);
    }


    /**
     * 获取淘宝同步详情时显示在商品尾部的图
     *
     * @return Array 形如:
        [
            'url' => 'xxx',
            'width' => xxx,
            'height' => xxx
        ]
     * 
     */
    public function getAdPic2 () {

        return json_decode($this->setting->ad_pic_2, true);
    }

    
    // 唤起旺旺的链接
    public function getEmitWangwangLink () {

        return $this->setting->emit_wangwang_link;
    }
}