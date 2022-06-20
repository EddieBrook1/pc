<?php
namespace app\controller\tb\xiangqing;

use app\BaseController;
use think\Request;
use think\facade\Validate;
use think\facade\Db;

use app\model\OldPaymentModel;
use app\model\OldXqGroupModel;
use app\model\OldDetailTemplateModel;
use app\model\OldXqGroupsUserModel;
use app\model\OldUserRoleModel;
use app\model\OldUserModel;

use app\extend\EditorDataValidator;
use app\extend\RecordError;

use app\extend\tb\TbUtil;
use app\extend\tb\TbXiangqingOnlineImgs;

use app\controller\tb\xiangqing\DetailToEditorData;
use app\controller\tb\xiangqing\EditorDataToDetail;
use app\controller\tb\xiangqing\XiangqingUtil;


class Index extends BaseController {

    // 模版数据的默认字段
    private const DEFAULT_DETAIL = [
        'template' => '',
        'hotspot' => '',
        'gif' => '',
        'video' => '',
    ];


    // 获取某个商品的数据
    public function customModule (Request $request) {

        $input = $request->get();
        if (!IndexValidator::customModule($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 用户uid
        $uid = $request->uid;
        // 商品id
        $goods_id = isset($input['goods_id']) ? $input['goods_id'] : -1;
        // 模版id
        $template_id = $input['template_id'];


        // 模版是否存在
        $xq_group = OldXqGroupModel::where('tmp_id', $template_id)->find();
        if ($xq_group == null) return $this->resultJson([], false, 'TEMPLATE_NULL_ERR');

        // 尝试获取模版数据
        $editor_data_raw = CustomModule::handle($uid, $template_id, $goods_id);
        if ($editor_data_raw === CustomModule::ERR_NO_GOODS_ID) return $this->resultJson([], false, 'PARAM_ERR', '缺少商品id');

        $result = [
            'data_type' => $editor_data_raw['data_type'],
            'editor_data' => $editor_data_raw['editor_data'],
            'goods_id' => $goods_id,
            'template_id' => $template_id,
        ];
        return $this->resultJson($result, true, 'GET_SUCC');
    }


    // 获取某个商品的数据, 内部接口使用
    public function customModuleSelf (Request $request) {

        $input = $request->get();
        if (!IndexValidator::customModule($input)) return $this->resultJson([], false, 'PARAM_ERR');
        
        // 用户uid
        $uid = $input['uid'];
        // 商品id
        $goods_id = isset($input['goods_id']) ? $input['goods_id'] : -1;
        // 模版id
        $template_id = $input['template_id'];


        // 模版是否存在
        $xq_group = OldXqGroupModel::where('tmp_id', $template_id)->find();
        if ($xq_group == null) return $this->resultJson([], false, 'TEMPLATE_NULL_ERR');

        // 尝试获取模版数据
        $editor_data_raw = CustomModule::handle($uid, $template_id, $goods_id);
        if ($editor_data_raw === CustomModule::ERR_NO_GOODS_ID) return $this->resultJson([], false, 'PARAM_ERR', '缺少商品id');

        $result = [
            'data_type' => $editor_data_raw['data_type'],
            'editor_data' => $editor_data_raw['editor_data'],
            'goods_id' => $goods_id,
            'template_id' => $template_id,
        ];
        return $this->resultJson($result, true, 'GET_SUCC');
    }


    // 获取模版原始数据
    public function designerModule (Request $request) {

        $input = $request->get();
        if (!IndexValidator::designerModule($input)) return $this->resultJson([], false, 'PARAM_ERR');

        // 模版id
        $template_id = $input['template_id'];

        // 模版是否存在
        $xq_group = OldXqGroupModel::where('tmp_id', $template_id)->find();
        if ($xq_group == null) return $this->resultJson([], false, 'TEMPLATE_NULL_ERR');

        // 转换模版数据
        $detail = array_merge(self::DEFAULT_DETAIL, [
            'template' => $xq_group->groups
        ]);
        $editor_data = DetailToEditorData::handle($detail);


        return $this->resultJson([
            'editor_data' => $editor_data,
            'template_id' => $template_id,
        ], true, 'GET_SUCC');
    }


    // 保存, 当用户装插件后; 通过 用户id + 商品id + 模版id 定位一条记录
    public function saveByGoods (Request $request) {

        $input = $request->post();
        $isOk = IndexValidator::saveByGoods($input);
        if ($isOk !== true) return $this->resultJson([], false, 'PARAM_ERR', $isOk);

        // 用户uid
        $uid = $request->uid;

        $template_id = $input['template_id'];
        $goods_id = $input['goods_id'];
        $editor_data = json_decode($input['editor_data'], true);

        // 模版是否存在
        if (!XiangqingUtil::isTemplateUsable($template_id)) return $this->resultJson([], false, 'TEMPLATE_NULL_ERR');

        // 查看是否过期
        if (!XiangqingUtil::isPayExpire($uid, $template_id)) return $this->resultJson([], false, 'SERVICE_NO');

        // 新数据格式转换成表数据
        $detail = EditorDataToDetail::handle($editor_data);

        // 保存数据; 事务, 防止重复
        Db::startTrans();
        try {
            
            // 加锁查询有没有这条数据
            $detailTemplate = OldDetailTemplateModel::where('tid', $template_id)
                ->where('gid', $goods_id)
                ->where('uid', $uid)
                ->order('id', 'DESC')
                ->lock(true)
                ->find();

            
            if ($detailTemplate) {

                // 有更新数据
                $detailTemplate->template = $detail['template'];
                $detailTemplate->hotspot = $detail['hotspot'];
                $detailTemplate->gif = $detail['gif'];
                $detailTemplate->video = $detail['video'];
                $detailTemplate->update_time = time();
                $detailTemplate->save();
            } else {

                // 没有创建一条
                $detailTemplate = OldDetailTemplateModel::create([
                    'template' => $detail['template'],
                    'uid' => $uid,
                    'tid' => $template_id,
                    'gid' => $goods_id,
                    'hotspot' => $detail['hotspot'],
                    'gif' => $detail['gif'],
                    'video' => $detail['video'],
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
            Db::commit();

        } catch (\Throwable $th) {

            Db::rollback();
            $detailTemplate = null;

            RecordError::handle(
                __CLASS__,
                $th->getMessage() . "; 入参(uid=$uid, template_id=$template_id, goods_id=$goods_id)",
                "详情模版数据保存失败(uid+tid+gid)"
            );
        }

        return $detailTemplate == null
            ? $this->resultJson([], false, 'SAVE_ERR')
            : $this->resultJson([], true, 'SAVE_SUCC');
    }


    // 保存, 当用户没装插件; 通过 用户id + 模版id 定位一条记录
    public function saveByTemplate (Request $request) {

        $input = $request->post();
        $isOk = IndexValidator::saveByTemplate($input);
        if ($isOk !== true) return $this->resultJson([], false, 'PARAM_ERR', $isOk);

        // 用户uid
        $uid = $request->uid;

        $template_id = $input['template_id'];
        $editor_data = json_decode($input['editor_data'], true);

        // 模版是否存在
        if (!XiangqingUtil::isTemplateUsable($template_id)) return $this->resultJson([], false, 'TEMPLATE_NULL_ERR');

        // 查看是否过期
        if (!XiangqingUtil::isPayExpire($uid, $template_id)) return $this->resultJson([], false, 'SERVICE_NO');

        // 新数据格式转换成表数据
        $detail = EditorDataToDetail::handle($editor_data);

        // 保存数据; 事务, 防止重复
        Db::startTrans();
        try {
            
            // 加锁查询有没有这条数据
            $xqGroupsUser = OldXqGroupsUserModel::where('user_id', $uid)
                ->whereNull('puser_id')
                ->where('tmp_id', $template_id)
                ->lock(true)
                ->find();

            $time = time();
            if ($xqGroupsUser) {

                // 有更新数据
                $xqGroupsUser->groups = $detail['template'];
                $xqGroupsUser->hotspot = $detail['hotspot'];
                $xqGroupsUser->gif = $detail['gif'];
                $xqGroupsUser->video = $detail['video'];
                $xqGroupsUser->update_time = $time;
                $xqGroupsUser->save();
            } else {

                // 没有创建一条
                $xqGroupsUser = OldXqGroupsUserModel::create([
                    'groups' => $detail['template'],
                    'tmp_id' => $template_id,
                    'user_id' => $uid,
                    'hotspot' => $detail['hotspot'],
                    'gif' => $detail['gif'],
                    'video' => $detail['video'],
                    'create_time' => $time,
                    'update_time' => $time
                ]);
            }
            Db::commit();
            
        } catch (\Throwable $th) {

            Db::rollback();
            $xqGroupsUser = null;

            RecordError::handle(
                __CLASS__,
                $th->getMessage() . "; 入参(uid=$uid, tid=$template_id)",
                "详情模版数据保存失败(uid+tid)"
            );
        }

        return $xqGroupsUser == null
            ? $this->resultJson([], false, 'SAVE_ERR')
            : $this->resultJson([], true, 'SAVE_SUCC');
    }


    // 判断用户是否购买该模版
    private function is_pay ($uid, $template_id) {

        $payment = OldPaymentModel::field('expiretime')
            ->where('template_id', $template_id)
            ->where('user_id', $uid)
            ->find();

        if ($payment == null) return false;

        return $payment->expiretime > time();
    }


    // 获取用户对该模版的权限
    public function templateAuth (Request $request) {

        // 验证入参
        $input = $request->get();
        if (!IndexValidator::templateAuth($input)) return $this->resultJson([], false, 'PARAM_ERR');


        // 用户id
        $uid = $request->uid;
        // 模版id
        $template_id = $input['template_id'];
        // 默认的返回值
        $default_result = [
            'tid' => $template_id,
            'is_height3w' => false,
            'is_gif' => false,
            'is_text' => false,
            'is_image' => false,
            'is_wimage' => false,
            'is_video' => false,
            'is_hvideo' => false,
            'is_hdv' => false,
            'is_pay' => false,
        ];


        // 如果是管理员, 拥有全部权限
        if (false) {
            $result = array_merge($default_result, [
                'is_height3w' => true,
                'is_gif' => true,
                'is_text' => true,
                'is_image' => true,
                'is_wimage' => true,
                'is_video' => true,
                'is_hvideo' => true,
                'is_hdv' => true,
                'is_pay' => true,
            ]);
            return $this->resultJson($result, true, 'GET_SUCC');
        }

        // 是否购买
        $is_pay = $this->is_pay($uid, $template_id);

        // 模版权限
        $userRole = OldUserRoleModel::where('tids', $template_id)
            ->where('pid', $uid)
            ->find();

        $userRole_data = $userRole
            ? $userRole->toArray()
            : $default_result;

        $time = time();
        $is_height3w = ($userRole_data['is_height3w'] == 1 && $userRole_data['h3_time'] > $time);
        $is_gif      = ($userRole_data['is_gif'] == 1 && $userRole_data['g_time'] > $time);
        $is_text     = ($userRole_data['is_text'] == 1 && $userRole_data['t_time'] > $time);
        $is_image    = ($userRole_data['is_image'] == 1 && $userRole_data['i_time'] > $time);
        $is_wimage   = ($userRole_data['is_wimage'] == 1 && $userRole_data['wi_time'] > $time);
        $is_video    = ($userRole_data['is_video'] == 1 && $userRole_data['v_time'] > $time);
        $is_hvideo   = ($userRole_data['is_hvideo'] == 1 && $userRole_data['hv_time'] > $time);
        $is_hdv      = ($userRole_data['is_hdv'] == 1 && $userRole_data['hdv_time'] > $time);

        $result = array_merge($default_result, [
            'is_height3w' => $is_height3w,
            'is_gif' => $is_gif,
            'is_text' => $is_text,
            'is_image' => $is_image,
            'is_wimage' => $is_wimage,
            'is_video' => $is_video,
            'is_hvideo' => $is_hvideo,
            'is_hdv' => $is_hdv,
            'is_pay' => $is_pay,
        ]);

        return $this->resultJson($result, true, 'GET_SUCC');
    }


    // 判断模版是否存在
    public function isTempalteExist (Request $request) {

        $input = $request->get();
        if (!IndexValidator::isTempalteExist($input)) return $this->resultJson(false, [], 'PARAM_ERR');

        $template_id = $input['template_id'];
        $xqGroup = OldXqGroupModel::where('tmp_id', $template_id)->find();
        
        return $xqGroup == null
            ? $this->resultJson([], true, 'UNDEFINED')
            : $this->resultJson([], true, 'EXIST');
    }


    // 获取线上数据
    public function onlineImgs (Request $request) {

        $input = $request->get();
        $isOk = IndexValidator::onlineData($input);
        if ($isOk !== true) return $this->resultJson([], false, 'PARAM_ERR');

        // 客户cookie
        $uid = $request->uid;
        $cookie = OldUserModel::where('user_id', $uid)->find()->cookie;

        $goods_id = $input['goods_id'];
        $result = (new TbXiangqingOnlineImgs)->handle($cookie, $goods_id);

        // 错误提前返回
        if ($result['error']) return $this->resultJson([], false, $result['code_name']);
        
        return $this->resultJson($result['data'], true, 'GET_SUCC');
    }
}


// 入参验证器
class IndexValidator {

    public static function customModule ($input) {

        $validate = Validate::rule([
            'goods_id' => 'integer',
            'template_id' => 'require|number',
        ]);

        return $validate->check($input);
    }


    public static function designerModule ($input) {

        $validate = Validate::rule([
            'template_id' => 'require|number'
        ]);

        return $validate->check($input);
    }


    public static function saveByGoods ($input) {

        // 基本验证
        $validate = Validate::rule([
            'template_id' => 'require|number',
            'goods_id' => 'require|number',
            'editor_data' => 'require',
        ]);
        if (!$validate->check($input)) return '缺少必要字段';

        // editor_data字段详细验证
        $isOk = EditorDataValidator::handle($input['editor_data']);
        if ($isOk !== true) return $isOk;

        return true;
    }


    public static function saveByTemplate ($input) {

        // 基本验证
        $validate = Validate::rule([
            'template_id' => 'require|number',
            'editor_data' => 'require',
        ]);
        if (!$validate->check($input)) return '缺少必要字段';

        // editor_data字段详细验证
        $isOk = EditorDataValidator::handle($input['editor_data']);
        if ($isOk !== true) return $isOk;

        return true;
    }


    public static function templateAuth ($input) {

        $validate = Validate::rule([
            'template_id' => 'require|number'
        ]);
        return $validate->check($input);
    }


    public static function isTempalteExist ($input) {
        $validate = Validate::rule([
            'template_id' => 'require|number'
        ]);
        return $validate->check($input);
    }


    public static function onlineData ($input) {

        $validate = Validate::rule([
            'goods_id' => 'require|number'
        ]);
        return $validate->check($input);

    }


    public static function customModuleSelf ($input) {

        $validate = Validate::rule([
            'uid' => 'require|number',
            'goods_id' => 'integer',
            'template_id' => 'require|number',
        ]);

        return $validate->check($input);
    }
}


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