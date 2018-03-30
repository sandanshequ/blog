<?php

namespace app\test\controller;
use  think\Controller;
use  app\test\model\BlogModel;
use think\Request;
use think\Validate;
use think\Db;
class BlogController extends Controller {
     
	/**
     * 博客列表
     * @return \think\response\View 
     * @throws \think\exception\DbException
     */
    public  function lists(){
    	//搜索条件
    	if($username = input('get.username')){
    		$this->assign('username',$username);
    		$where[] = ['username','=',"$username"];
    	}
    	if($title = input('get.title')){
    		$this->assign('title',$title);
    		$where[] = ['title','like',"%$title%"];
    	}
    	$where[] = ['status','=',2];

    	$lists =Db::name('blog')
    			->alias('a')
    			->join('user b','a.uid = b.uid')
    	        ->where($where)
    	        ->order('a.add_time','desc')
    	        ->field('a.title,a.bid,b.username')
    	        ->paginate(3,'',[
    	        	'query' => ['username'=>$username , 'title' => $title ],
    	          ]);

    	// echo Db::getLastSql();
    	$this->assign('list',$lists);
    	return $this->fetch('blog/blog_lists');
    }
    
	/**
     * 添加用户
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
	public function add(){
		if(input('get.act')){

			//添加入库
			$data['title'] = input('post.title');
			$data['content'] = input('post.content');
			$data['img_url'] = input('post.image_path');
			$data['uid'] = session('uid');
			$data['add_time'] = date('Y:m:d H:s:i',time());
			//验证数据格式
			$this->checkData($data);
        	
        	//添加入库
			$blog = new BlogModel();
			$blogInfo = $blog->save($data);
			if($blogInfo){
				$this->success('提交成功！','/test/blog/lists');
			}else{
				$this->error('提交内容失败！');
			}
		}else{
			return $this->fetch('/blog/blog_add');
		}
		
	}


	/**
     * 修改用户
     
     */
	public function modify(){
		if(input('get.act')){
			$bid = input('post.bid');
			//添加入库
			$data['title'] = input('post.title');
			$data['content'] = input('post.content');
			$data['img_url'] = input('post.image_path');
			$data['uid'] = session('uid');
			$data['add_time'] = date('Y:m:d H:s:i',time());
			//验证数据格式
			$this->checkData($data);

        	//添加入库
			$blog = new BlogModel();
			$blogInfo = $blog->where('bid',$bid)->update($data);
			if($blogInfo){
				$this->success('修改成功！','/test/blog/lists');
			}else{
				$this->error('修改内容失败！');
			}
		}else{

			if(!$bid = input('get.bid')){
				$this->error('未接收到bid！');
			}

			$blog = new BlogModel();
			$bInfo = $blog->where('bid',$bid)->field('bid,title,content,img_url')->find();

			$this->assign('bInfo',$bInfo);
			return $this->fetch('/blog/blog_modify');
		}
		
	}


	/**
     * 删除用户
    */
   public function delete(){
   	    if(!$bid = input('get.bid')){
			$this->error('未接收到bid！');
		}

		$data['status'] = 1;
		$blog = new BlogModel();
		$res = $blog->where('bid',$bid)->update($data);
		if($res){
			$this->success('删除成功！','/test/blog/lists');
		}else{
			$this->error('删除失败！');
		}
   }



	/**
     * 上传图片 
     * @author caoyw
     * @throws \think\exception\DbException
     */
    public function upload(){
    	//判断有无接收到图片
		if($file = request()->file('img_url')){
			
	    	$uplaod_path = "./upload_file/image/";
	    	$config['ext'] = "jpg,png,git";
	    	$config['size'] = 10 * 1024 * 1024;

	    	if(!file_exists($uplaod_path)){
	    		mkdir($uplaod_path,0777,true);
	    	}
	    	$fileInfo = $file->validate($config)->move($uplaod_path);
	    	if($fileInfo){
	    		$new_path = strstr($uplaod_path,'/');
	    		$date = date("Ymd");
	    		$jsonInfo['status'] = 1;
	    		$jsonInfo['realname'] = $date."/".$fileInfo->getFilename();//图片真实名称
	    		$jsonInfo['savename'] = $new_path.$fileInfo->getSaveName();//图片存储名称
	    		echo  json_encode($jsonInfo);exit;
	    	}else{
	    		echo $file->getError();exit;
	    	}
		}else{
			$this->error('请上传图片!');
		}
    }


    /**
     * 验证数据 
     * @author caoyw
     * @throws \think\exception\DbException
     */
    public  function checkData($data){
    	$validate = new Validate([
				'title' => 'require|max:25',
				'content' => 'require',
				'img_url' => "require",
		]);
		$validate->message([
			'title.require' => "请输入标题",
			'title.max' => "标题字数已超过25",
		 	'cotent.require' => '请输入规定好字数的内容',
		 	'img_url.require' => '请上传照片',
		]);
		if(!$validate->check($data)){
			$this->error($validate->getError());
		}
    }



}