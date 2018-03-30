<?php

namespace app\test\controller;
use  think\Controller;
use  app\test\model\CommentModel;
// use think\Request;
use think\Validate;
use think\Db;
class CommentController extends Controller {
     
	/**
     * 博客信息详情页和评论列表展示
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public  function lists(){
    	//搜索条件
    	if(!$bid = input('get.bid')){
    		$this->error('未接收到bid');
    	}
    	
    	// 博客详情查询
    	$bInfo =Db::name('blog')
    	        ->where('bid',$bid)
    	        ->field('title,bid,content')
    	        ->find();

    	// 评论列表展示
    	$cInfo = Db::name('comment')
    			->alias('a')
    			->join('user b','a.add_id = b.uid')
    	        ->where('bid',$bid)
    	        ->where('status',2)
    	        ->order('add_time','desc')
    	        ->field('a.cid,a.content,a.add_time,b.username')
    	        ->select();
    	// echo Db::getLastSql();exit;
    	$this->assign('bInfo',$bInfo);
    	$this->assign('cInfo',$cInfo);
    	return $this->fetch('comment/comment_lists');
    }
    
	/**
     * 提交评论
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
	public function add(){

		if(input('post.content')){
			//添加入库
			$data['bid'] = input('post.bid');
			$data['content'] = input('post.content');
			$data['add_id'] = session('uid');
			$data['add_time'] = date('Y:m:d H:s:i',time());
			//验证数据格式
			$this->checkData($data);
        	
        	//添加入库
			$commnet = new CommentModel();
			$cInfo = $commnet->save($data);
			if($cInfo){
				$this->success('1'); //  1表示提交成功
			}else{
				$this->error('3'); // 3表示提交失败 
			}
		}else{
			$this->error('2');//  2表示没有评论就提交
		}
		
	}

	

	/**
     * 删除评论
    */
   public function delete(){
   	    if(!$cid = input('get.cid')){
			$this->error('未接收到cid！');
		}
		if(!$bid = input('get.bid')){
			$this->error('未接收到bid！');
		}

		$data['status'] = 1;
		$comment = new CommentModel();
		$res = $comment->where('cid',$cid)->update($data);
		if($res){
			$this->success('删除成功！',"/test/comment/lists?bid={$bid}");
		}else{
			$this->error('删除失败！');
		}
   }


    /**
     * 验证数据 
     * @author caoyw
     * @throws \think\exception\DbException
     */
    public  function checkData($data){
    	$validate = new Validate([
			'content' => 'require|max:300',
		]);
		$validate->message([
			'content.max' => "标题字数已超过300",
		]);
		if(!$validate->check($data)){
			$this->error('4'); // 4表示超过规定字数
		}
    }



}