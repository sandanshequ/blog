<?php

namespace app\test\controller;
use  think\Controller;
use  app\test\model\UserModel;

class LoginController extends Controller {

	/**
     * 用户登录
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
	public function login(){
		if(input('get.act')){
			//根据用户名判断是否存在该用户
			$username = input('post.username');
			$passwrod = input('post.password');

			$user = new UserModel();
			$userInfo = $user->where('username',$username)->field('uid,passwd')->find();
			if($userInfo){
				if($passwrod == $userInfo['passwd']){
					session('uid',$userInfo['uid']);
					session('username',$username);
					$this->success('用户登录成功！',"/test/blog/add");
				}
			}else{
				$this->error('该用户不存在！');
			}
		}else{
			return view();
		}
		
	}

	/**
     * 退出登录
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function logout(){
    	session(null);
    	$this->success('退出登录成功！','/test/login/login');
    }


}