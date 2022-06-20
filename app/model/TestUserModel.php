<?php
namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;


/**
 * 此表为测试表, 给新员工熟悉ajax使用
 */
class TestUserModel extends Model {
    
    use SoftDelete;

    // 支持自动时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 软删除默认值
    protected $defaultSoftDelete = 0;

    // 表名
    protected $table = 'test_users';

    // 模型字段
    protected $schema = [
        'id' => 'int',
        'name' => 'varchar',
        'age' => 'int',
        'create_time' => 'bigint',
        'update_time' => 'bigint',
        'delete_time' => 'bigint',
    ];
}
