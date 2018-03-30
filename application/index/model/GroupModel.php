<?php

namespace app\index\model;

use think\Db;
use think\Model;

class GroupModel extends BaseModel
{
    protected $table = "auth_groups";

    public function users()
    {
        return $this->belongsToMany('UserModel', 'auth_user_groups', 'user_id', 'group_id');
    }

}




