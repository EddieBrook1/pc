<?php
namespace app\model;

use think\Model;


/**
 * 此表存储websocket用户的登录信息
 */
class OldTemplateModel extends Model {

    // 连接
    protected $connection = 'origin';

    // 表名
    protected $table = 'template';

    // 模型字段, 处理id, 其他全是可选
    protected $schema = [
        'template_id' => 'int',
        'emplateName' => 'varchar',     // 模板名称
        'price' => 'varchar',           // 价格
        'type' => 'varchar',            // 类型
        'style' => 'varchar',           // 风格
        'classify' => 'varchar',        // 主图分类
        'imgurl' => 'mediumtext',       // 预览图路径
        'details' => 'varchar',         // 模板详情
        'templateurl' => 'varchar',     // 模板存放路径
        'createTime' => 'varchar',      // 创建时间
        'user_id' => 'int',             // 设计人id
        'addtemTime' => 'varchar',      // 最后修改时间
        'templateState' => 'enum',      // 模板状态：1未发布，2已上架，3已下架，4定制，5未打标，6神笔
        'userUse' => 'varchar',         // 设计师名称
        'useNumber' => 'int',           // 使用总人数
        'status' => 'int',              // 状态：1.详情，2.首页，3.海报，4.主图标签 5.详情标签 6.店铺logo 7.（预留）拼多多首页 8.简历模板，9. PC首页, 10.海报,  11.店招
        'client' => 'int',              // 客户终端应用类型：1为手机端，2为电脑端
    ];
}
