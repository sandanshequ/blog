<?php
/**
 * Created by PhpStorm.
 * User: NilTor
 * Date: 3/21/2018
 * Time: 2:11 PM
 */

namespace app\index\model;


use think\Model;

class BaseModel extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}