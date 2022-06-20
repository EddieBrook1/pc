<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class OldPaymentModule extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'payment';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'id'                => 'int',       // 
        'user_id'           => 'varchar',   // 用户id
        'template_id'       => 'varchar',   // 模板id
        'paymenttime'       => 'bigint',    // 付款时间
        'expiretime'        => 'bigint',    // 到期时间
        'money'             => 'varchar',   // 付款金额
        'time_length'       => 'varchar',   // 购买的时长
        'mobilePhone'       => 'varchar',   // 手机
        'wangwang'          => 'varchar',   // 旺旺
        'useNumber'         => 'varchar',   // 订单对应模板操作次数
        'start'             => 'varchar',   // 1正常，2过期，3过期一个月数据被清除
        'times'             => 'varchar',   // 最后一次保存时间
        'allOne'            => 'int',       // 模式1
        'allTwo'            => 'int',       // 模式2
        'method'            => 'varchar',   // 1自主添加  2支付宝
        'status'            => 'int',       // 状态栏：1.详情模板，2.首页模板，3.海报模板，4.全图模板，9. PC首页模版
        'next_paytime'      => 'bigint',    // 上一次续费时间
        'UndoneType'        => 'enum',      // 订单类型: 1,设计 2,照拆 3，装修
        'stylist'           => 'varchar',   // 设计师
        'due_date'          => 'int',       // 期限
        'get_time'          => 'bigint',    // 领取时间
        'perform'           => 'int',       // 完成度： 1，未完成 2，已完成
    ];
}
