<?php

namespace app\index\model;

use think\Model;
use think\model\concern\SoftDelete;

class UserModel extends BaseModel
{

    use SoftDelete;
    protected $table = "auth_users";
    protected $deleteTime = 'deleted_at';

    protected $type = ['deleted_at' => 'datetime'];


    /**
     * 关联组
     * @return \think\model\relation\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany('GroupModel', 'auth_user_groups', 'group_id', 'user_id');
    }


    /**
     * 列表查询
     * @param int $page_size
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getList($page_size = 12)
    {
        return $this->order('created_at', 'desc')
            ->paginate($page_size);
    }


    /**
     * 是否存在
     * @param string $username
     * @param string $password
     * @return array|null|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isExist(string $username, string $password)
    {
        $user = $this->where('username', $username)
            ->where('password', hash('sha256', $password))
            ->with(['groups' => function ($query) {
                $query->field('id,name');
            }])
            ->find();

        return $user ?? [];
    }


    /**
     * 添加
     * @param array $data
     * @return static
     */
    public function add($data = [])
    {
        $group_ids = $data['group_ids'] ?? null;
        $user      = UserModel::create(['username' => $data['username'],
                                        'password' => hash('sha256', $data['password'])]
        );
        // 设置了群组信息
        if (!empty($group_ids))
        {
            $user->groups()->saveAll($group_ids);
        }
        return $user;

    }

    /**
     * 更新
     * @param $id
     * @param $group_ids
     * @return false|int|null|static
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function updateUser($id, $group_ids)
    {
        $user = UserModel::get($id);

        foreach ($group_ids as $key => $val)
        {
            $group_ids[$key] = (int)$val;
        }
        // 设置了群组信息
        if (!empty($group_ids))
        {
            $user->groups()->detach();
            $user->groups()->attach($group_ids);
        }
        return $user;
    }


    /**
     * 删除
     * @param $id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public function del($id)
    {
        $user = UserModel::get($id);
        $user->groups()->detach();
        $user->delete();
        return $user;
    }

}




