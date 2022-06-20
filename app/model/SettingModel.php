<?php
namespace app\model;

use think\Model;


class SettingModel extends Model {

    // 表名
    protected $table = 'setting';

    // 模型字段
    protected $schema = [
        // 任意一个商家cookie
        'tb_cookie_seller' => 'text',
        // 深圳千知鱼设计有限公司 账号登录后的cookie
        'tb_cookie_isv' => 'text',
        // 已调用淘宝开发者查询旺旺的次数
        'wangwang_by_token_queryed' => 'int',
        // 开发者的旺旺名
        'isv_wangwang' => 'varchar',
        // 同步详情时显示在旺铺编辑器里的第一张图
        'ad_pic_1' => 'text',
        // 同步详情时显示在商品里最后一张图
        'ad_pic_2' => 'text',
        // 换起旺旺的链接
        'emit_wangwang_link' => 'text',
    ];
}
