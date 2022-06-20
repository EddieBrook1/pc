<?php
namespace app\extend\tb;


class CustomModule {

    public const DATA_TYPE_ONLINE = 'online';
    public const DATA_TYPE_DRAFT = 'draft';
    public const DATA_TYPE_ORIGIN = 'origin';

    public const ERR_NO_GOODS_ID = 'ERR_NO_GOODS_ID';


    // 模版数据的默认字段
    private const DEFAULT_DETAIL = [
        'template' => '',
        'hotspot' => '',
        'gif' => '',
        'video' => '',
    ];
    

    /** cookie可用时的逻辑
     * 无商品id(id不为数值), 返回错误提示
     * 有商品id, 按以下优先级返回数据
     *      1. 线上数据
     *      2. 本地数据
     *      3. 原始数据
     *
     * @param [Number] $uid 用户id
     * @param [Number] $template_id 模版id
     * @param [Number] $goods_id 商品id
     * @return false|Array
     */
    private static function whenCookieOk ($uid, $template_id, $goods_id) {

        // 无商品id, 返回错误要求选择商品id
        if (!is_numeric($goods_id) || $goods_id == -1) self::ERR_NO_GOODS_ID;

        $data_type = null;

        // 1. 尝试查找线上数据
        $detail_raw = OldDetailTemplateModel::field('template, hotspot, gif, video')
            ->where('uid', $uid)
            ->where('tid', $template_id)
            ->where('gid', $goods_id)
            ->find();
        if ($detail_raw) {
            $detail = array_merge(self::DEFAULT_DETAIL, $detail_raw->toArray());
            $data_type = self::DATA_TYPE_ONLINE;
        }


        // 2. 线上数据不存在, 尝试查找本地数据
        if ($detail_raw == null) {
            $detail_raw = OldXqGroupsUserModel::field('groups as template, hotspot, gif, video')
                ->where('tmp_id', $template_id)
                ->where('user_id', $uid)
                ->find();
            if ($detail_raw) {
                $detail = array_merge(self::DEFAULT_DETAIL, $detail_raw->toArray());
                $data_type = self::DATA_TYPE_DRAFT;
            }
        }


        // 3. 线上和本地都不存在, 返回原始数据
        if ($detail_raw == null) {
            $detail_raw = OldXqGroupModel::field('groups as template')
                ->where('tmp_id', $template_id)
                ->find();

            if ($detail_raw) {
                $detail = array_merge(self::DEFAULT_DETAIL, $detail_raw->toArray());
                $data_type = self::DATA_TYPE_ORIGIN;
            }
        }

        return [
            'data_type' => $data_type,
            'editor_data' => DetailToEditorData::handle($detail),
        ];
    }


    /** cookie不可用时的逻辑
     * 按以下逻辑返回数据
     *      1. 本地数据
     *      2. 原始数据
     *
     * @param [Number] $uid 用户id
     * @param [Number] $template_id 模版id
     * @return Array
     */
    private static function whenCookieNo ($uid, $template_id) {

        $data_type = null;

        // 1. 本地数据
        $detail_raw = OldXqGroupsUserModel::field('groups as template, hotspot, gif, video')
            ->where('tmp_id', $template_id)
            ->where('user_id', $uid)
            ->find();
        if ($detail_raw) {
            $detail = array_merge(self::DEFAULT_DETAIL, $detail_raw->toArray());
            $data_type = self::DATA_TYPE_DRAFT;
        }

        // 2. 本地数据没有, 返回原始数据
        if ($detail_raw == null) {
            $detail_raw = OldXqGroupModel::field('groups as template')
                ->where('tmp_id', $template_id)
                ->find();
            if ($detail_raw) {
                $detail = array_merge(self::DEFAULT_DETAIL, $detail_raw->toArray());
                $data_type = self::DATA_TYPE_ORIGIN;
            }
        }

        return [
            'data_type' => $data_type,
            'editor_data' => DetailToEditorData::handle($detail),
        ];
    }


    /** 入口
     *  分两种情况, cookie可用和cookie不可用的情况, 对应前端的即是有插件和无插件的情况
     * 
     * @param [Number] $uid
     * @param [Number] $template_id
     * @param [Number] $goods_id
     * @return String|Array
     */
    public static function handle ($uid, $template_id, $goods_id) {
        
        $cookie = OldUserModel::field('cookie')
            ->where('user_id', $uid)
            ->find()
            ->cookie;

        $is_cookie_ok = TbUtil::isCookieValid($cookie);
        $result = $is_cookie_ok
            ? self::whenCookieOk($uid, $template_id, $goods_id)
            : self::whenCookieNo($uid, $template_id);
            
        return $result;
    }
}