<?php

namespace app\index\controller;

use app\index\model\GroupModel;
use app\index\model\UserModel;
use think\Controller;
use think\Db;


/**
 * 用户管理控制器，仅限管理员权限
 * Class UserController
 * @package app\index\controller
 */
class UserController extends CommonController
{
    function initialize()
    {
        parent::initialize();
    }


    /**
     * 显示资源列表
     *
     * @param int $page_size
     * @return \think\Response
     * @throws \think\exception\DbException
     */
    public function list(int $page_size = 10)
    {
        $this->setAuthorize(1);

        $model = new UserModel();
        $list  = $model->getList($page_size);

        $this->assign('list', $list);
        $this->assign('title', '用户列表-用户管理');
        $this->assign('nav_name', 'user/list');

        return view();
    }

    /**
     * 添加用户
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $this->setAuthorize(1);

        // 查询所有用户组
        $groups = GroupModel::all();
        $this->assign('groups', $groups);
        $this->assign('title', '添加用户-用户管理');
        $this->assign('nav_name', 'user/add');

        return view();
    }

    /**
     * 显示编辑资源表单页.
     * @param  int $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $this->setAuthorize(1);

        $model = new UserModel();
        $data  = $model->with('groups')->find($id);
        // 处理用户角色，变成存储id的数组
        $roles = $data['groups'];
        $roles = array_column($roles->toArray(), 'id');
        // 查询所有用户组
        $groups = GroupModel::all();

        $this->assign('groups', $groups);
        $this->assign('data', $data);
        $this->assign('roles', $roles);
        $this->assign('title', '用户权限修改-用户管理');
        return view();
    }

    /**
     * 修改用户组
     * @param null  $id
     * @param array $roles
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function editGroupAction($id = null, $roles = [])
    {
        if (empty($id) || empty($roles))
        {
            return $this->ajaxFail('请求参数错误');
        }
        $model = new UserModel();

        //                return $this->ajaxSuccess($roles);
        $result = $model->updateUser($id, $roles);
        // 同时添加被修改状态
        Db::table('tbl_login_status')->insert(['user_id' => $id]);

        if ($result)
        {
            return $this->ajaxSuccess($result);
        }
        return $this->ajaxFail('更新失败');
    }

    /**
     * 添加用户
     * @param string $username
     * @param string $password
     * @param array  $roles
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function addAction($username = '', $password = '', $roles = [])
    {

        if (empty($username) || empty($password) || empty($roles))
        {
            return $this->ajaxFail('参数错误');
        }

        if (UserModel::get(['username' => $username]))
        {
            return $this->ajaxFail('已存在该用户');
        }

        $model  = new UserModel();
        $result = $model->add(['username'  => $username,
                               'password'  => $password,
                               'group_ids' => $roles]);

        if ($result)
        {
            return $this->ajaxSuccess($result);
        }
        return $this->ajaxFail('添加失败');
    }

    /**
     * 删除用户
     * @param $id
     * @throws \think\exception\DbException
     */
    public function deleteAction($id)
    {
        if (empty($id))
        {
            $this->error('非法操作');
        }

        $model = new UserModel();

        $result = $model->del($id);
        if ($result)
            $this->success('删除成功', '/index/user/list', null, 1);

        $this->error('删除失败', '/index/user/list', null, 1);
    }

    /**
     * 退出登录
     */
    public function logoutAction()
    {
        session(null);
        return redirect('index/login/login');
    }

}
