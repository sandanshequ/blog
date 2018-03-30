<?php

namespace app\index\controller;


use app\index\model\ComponentModel;
use app\index\model\EdaUpdateMessage;
use app\index\model\RecordModel;
use app\index\model\UserModel;

class IndexController extends CommonController
{
    function initialize()
    {
        parent::initialize();
    }


    /**
     * 列表页
     * @param int $page      页数
     * @param int $page_size 每页数量
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(int $page_size = 12)
    {
        $model = new UserModel();
        $list  = $model->getList($page_size);
        $this->assign('list', $list);
        $this->assign('title', "CETC20元器件数据平台");
        return view();
    }

    /**
     * 详情页
     * @param int $id
     */
    public function detail(int $id)
    {

    }

    /**
     * 登录
     */
    public function login()
    {
        $this->assign('title', '登录-CETC20元器件数据平台');
        return view();
    }

    /**
     * 通知
     * @param int $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function inform($page_size = 12)
    {

        $model = new EdaUpdateMessage();

        $list = $model->order('created_at', 'desc')
            ->where('user_id',$this->user['id'])
            ->paginate($page_size);

        $this->assign('list', $list);
        $this->assign('title', '通知列表-CETC20元器件数据平台');
        return view();
    }

    /**
     * 元器件申请列表
     * @param int $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function data_status($page_size = 12)
    {
        $model = new RecordModel();
        $list  = $model->getUserRecordsByType(RecordModel::TYPE_COMPONENT, $page_size);

        $this->assign('list', $list);
        $this->assign('title', '元器件修改申请列表-CETC20元器件数据平台');
        $this->assign('nav_name', 'index/data_status');
        return view();
    }


    /**
     * eda申请列表
     * @param int $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function eda_status($page_size = 12)
    {
        $model = new RecordModel();
        $list  = $model->getUserRecordsByType(RecordModel::TYPE_EDA, $page_size);
        $this->assign('list', $list);
        $this->assign('title', 'EDA修改申请列表-CETC20元器件数据平台');
        $this->assign('nav_name', 'index/eda_status');

        return view();
    }

    /**
     * 修改密码
     * @return \think\response\View
     */
    public function change_password()
    {
        $this->assign('title', '修改密码-CETC20元器件数据平台');
        $this->assign('nav_name', 'index/change_password');

        return view();
    }


    /**
     * 修改密码
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function changePasswordAction()
    {
        $current_password = input('post.current_password');
        $newpassword      = input('post.newpassword');
        if (empty($current_password) || empty($newpassword))
        {
            $this->ajaxFail('非法参数');
        }

        $loginUser = session('user');
        $user      = UserModel::get($loginUser['id']);

        if ($user['password'] != hash('sha256', $current_password))
        {
            return $this->ajaxFail('原密码错误');
        }
        $user->password = hash('sha256', $newpassword);
        $user->save();
        return $this->ajaxSuccess($user);
    }


    /**
     * 获取最新升级提醒信息
     */
    public function getLastpdatedMsgAction()
    {
        $params['user_id'] = $this->user['id'];
        $re                = $this->getData('/eda/lastmsg', $params);
        return $this->ajaxSuccess($re['data']);
    }

    /**
     * 标记已读提醒
     */
    public function readUpdatedMsgAction()
    {
        $params['id']      = input('post.id');
        $params['user_id'] = $this->user['id'];

        if (empty($params['id']))
        {
            return $this->ajaxFail('缺少参数id');
        }
        $re = $this->updateData('/eda/msg/', $params);

        if ($re['status'] == 1)
        {
            return $this->ajaxSuccess($re['data']);
        }
        return $this->ajaxFail($re['data']);

    }

    /**
     * 测试
     * @param $cid
     * @param $sn
     */
    public function test($cid, $sn)
    {
        $model = new ComponentModel();
        $data  = $model->getFromEs($cid, $sn);

        dump($data);


    }
}
