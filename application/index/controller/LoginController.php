<?php

namespace app\index\controller;


use app\index\model\UserModel;
use think\Controller;

class LoginController extends Controller
{

    /**
     * 登录
     */
    public function login()
    { 
        $this->assign('title', '登录-CETC20元器件数据平台');
        return view();
    }

    /**
     * 登录
     * @param string $username
     * @param string $password
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function loginAction(string $username, string $password)
    {
        if (empty($username) || empty($password))
        {
            return json(['data' => '参数错误', 'status' => 0]);
        }

        $model = new UserModel();

        if ($user = $model->isExist($username, $password))
        {

            $groups = $user['groups'];
            unset($user['groups']);

            $group_ids = array_column($groups->toArray(), 'id');
//            session('groups', $groups);
            session('group_ids', $group_ids);
            session('user', $user);

            return json(['data' => $user, 'status' => 1]);
        }
        return json(['data' => '用户名或密码错误', 'status' => 0]);
    }
}
