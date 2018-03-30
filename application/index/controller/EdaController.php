<?php

namespace app\index\controller;

use app\index\model\ComponentModel;
use app\index\model\ComponentReviewModel;
use app\index\model\EdaReviewModel;
use think\Controller;
use think\Request;

/**
 * EDA管理页
 * Class EdaController
 * @package app\index\controller
 */
class EdaController extends CommonController
{

    protected $role;

    function initialize()
    {
        parent::initialize();
    }


    public function assignRole()
    {
        $group_ids = session('group_ids');
        // 管理员
        if (in_array(1, $group_ids) || in_array(5, $group_ids))
        {
            $this->assign('role', 'admin');
            $this->role = 'admin';
        } else
        {
            $this->assign('role', 'normal');
            $this->role = 'normal';
        }
    }

    /**
     * eda资源列表
     *
     * @param string|null $key
     * @param int         $page_size
     * @return \think\Response
     * @throws \think\exception\DbException
     */
    public function list(string $key = null, int $page_size = 10)
    {
        $this->setAuthorize(5);

        $model = new ComponentModel();
        $list  = $model->getList($key, $page_size);

        $this->assign("list", $list);
        $this->assign('title', 'EDA文件库管理');
        return view();
    }

    /**
     * eda 编辑资源表单页.
     * @param $sn
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($sn)
    {

        if (empty($sn))
        {
            $this->error("非法参数sn");
        }
        // 查询元器件详情
        $model = new ComponentModel();
        $data  = $model->getComponentDetail($sn);

        $this->assignRole();
        $this->assign('data', $data);
        $this->assign('title', 'EDA编辑修改');
        return view();

    }


    /**
     * eda审核列表页
     * @param string|null $q
     * @param int         $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function review_list(string $q = null, int $page_size = 10)
    {
        $this->setAuthorize(3);

        $model = new EdaReviewModel();
        $list  = $model->getList($q, $page_size);

        $this->assign("list", $list);
        $this->assign('title', 'EDA审核列表');
        $this->assign('nav_name', 'eda/review_list');

        //        dump($list);exit;
        return view();
    }

    /**
     * eda审核详情页
     * @param $sn
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function review_detail($sn)
    {
        $this->setAuthorize(3);

        if (empty($sn))
        {
            $this->error("非法参数pid");
        }
        $model = new ComponentModel();
        $data  = $model->getComponentDetail($sn);

        $review = $data['eda_review'];
        unset($data['component_review']);
        unset($data['eda_review']);

        $this->assign('component', $data);
        $this->assign('review', $review);
        $this->assign('title', 'EDA审核详情');

//        dump($review);exit;
        return view();
    }


    /**
     * EDA审核方法
     * 文档:http://open-docs.cissdata.com:2048/cetcjie-kou-wen-dang/edaguan-li-jie-kou.html#091af0a67a6e83ab494e6b8f41e1693d
     */
    public function reviewAction()
    {
        $params         = input('post.');
        $id             = input('post.id');
        $lib_name       = input('post.lib_name');
        $status         = input('post.status');
        $footprint_name = input('post.footprint_name');

        if (empty($id))
        {
            return $this->ajaxFail('no id');
        }

        $review_path = './eda/review/';
        $eda_path    = './eda/';

        if (!is_dir($review_path))
        {
            mkdir($review_path);
        }

        if (file_exists($review_path . $lib_name) && is_file($review_path . $lib_name))
        {
            rename($review_path . $lib_name, $eda_path . $lib_name);
        }
        if (file_exists($review_path . $footprint_name) && is_file($review_path . $lib_name))
        {
            rename($review_path . $footprint_name, $eda_path . $footprint_name);
        }

        if ($status == "通过")
        {
            $filename = './eda/eda.zip';
            if (file_exists($filename))
            {
                unlink($filename);
            }
            //重新生成文件
            $zip = new \ZipArchive();
            if ($zip->open($filename, \ZipArchive::CREATE) !== true)
            {
                return $this->ajaxFail('无法打开文件');
            }

            // 获取目录下所有文件
            $handle     = opendir($eda_path);
            $array_file = array();
            while (false !== ($file = readdir($handle)))
            {
                if ($file != "." && $file != ".." && !empty($file))
                {
                    $file_info['path'] = $eda_path . DIRECTORY_SEPARATOR . $file; //输出文件名
                    $file_info['name'] = $file;
                    $array_file[]      = $file_info;
                }
            }
            closedir($handle);
            foreach ($array_file as $val)
            {
                if (file_exists($val['path']) && is_file($val['path']))
                {
                    $zip->addFile($val['path'], $val['name']);
                }
            }
            $zip->close();
        }

        $params['review_user_id'] = $this->user['id'];
        $re                       = $this->updateData('/eda/review/', $params);
        return $this->ajaxSuccess($re['data']);
    }


    /**
     * eda文件上传,统一调用该方法
     */
    public function uploadFileAction()
    {

        $this->assignRole();
        if ($this->role == 'normal')
        {
            return $this->userUploadFile();
        } else
        {
            return $this->adminUploadFile();
        }
    }

    /**
     * 管理员上传EDA文件，会直接修改并打包
     */
    public function adminUploadFile()
    {
        $file           = request()->file('file');
        $upload_path    = "./eda";
//        $config['ext']  = 'pcblib,schlib';
        $config['size'] = 50 * 1024 * 1024;

        if (empty($file))
        {
            return $this->ajaxFail('无效的文件');
        }
        //创建目录
        if (!is_dir($upload_path))
        {
            mkdir($upload_path, 0777, true);
        }

        $info = $file->validate($config)
            ->move($upload_path, '');
        if ($info)
        {
            //成功上传后获取上传信息
            $params['name'] = $info->getFilename();
            $params['path'] = "/eda/" . $info->getSaveName();

            $filename = './eda/eda.zip';
            if (file_exists($filename))
            {
                unlink($filename);
            }
            //重新生成文件
            $zip = new \ZipArchive();
            if ($zip->open($filename, \ZipArchive::CREATE) !== true)
            {
                return $this->ajaxFail('无法打开文件', ['Content-Type' => 'text/plain']);
            }

            // 获取目录下所有文件
            $handle     = opendir($upload_path);
            $array_file = array();
            while (false !== ($file = readdir($handle)))
            {
                if ($file != "." && $file != ".." && !empty($file))
                {
                    $file_info['path'] = $upload_path . DIRECTORY_SEPARATOR . $file; //输出文件名
                    $file_info['name'] = $file;
                    $array_file[]      = $file_info;
                }
            }
            closedir($handle);
            foreach ($array_file as $val)
            {
                if (file_exists($val['path']) && is_file($val['path']))
                {
                    $zip->addFile($val['path'], $val['name']);
                }
            }
            $zip->close();
            return $this->ajaxSuccess([
                'file_path' => $params['path'],
                'orig_name' => $params['name']
            ], ['Content-Type' => 'text/plain']);
        } else
        {
            //上传失败获取错误信息
            return $this->ajaxFail($file->getError(), ['Content-Type' => 'text/plain']);
        }
    }

    /**
     * EDA修改提交
     */
    public function editAction()
    {
        $params = input('post.');
        $id     = $params['id'];
        if (empty($id))
        {
            return $this->ajaxFail("缺少参数id");
        }
        $params['user_id'] = $this->user['id'];

        $group_ids = session('group_ids');

        // 管理员身份
        if (in_array(1, $group_ids) || in_array(5, $group_ids))
        {
            $re = $this->updateData('/eda/sn/', $params);
        } else
        {
            $re = $this->updateData('/eda/review/sn/', $params);
        }
        if ($re['status'] == 1)
        {
            return $this->ajaxSuccess($re['data']);
        }
        return $this->ajaxFail($re['data']);

    }

    /**
     * 用户上传EDA文件
     */
    public function userUploadFile()
    {
        $file = request()->file('file');

        $upload_path    = "./eda/review/";
        $config['size'] = 50 * 1024 * 1024;
        $config['ext']  = 'pcblib,schlib';
        //        $config['type'] = 'application/octet-stream';
        if (empty($file))
        {
            return $this->ajaxFail('无效的文件', ['Content-Type' => 'text/plain']);
        }
        //创建目录
        if (!is_dir($upload_path))
        {
            mkdir($upload_path, 0777, true);
        }

        $info = $file->validate($config)
            ->move($upload_path, '');

        if ($info)
        {
            //成功上传后获取上传信息
            $params['name'] = $info->getFilename();
            $params['path'] = $upload_path . $info->getSaveName();

            return $this->ajaxSuccess([
                'file_path' => $params['path'],
                'orig_name' => $params['name']
            ], ['Content-Type' => 'text/plain']);
        } else
        {
            //上传失败获取错误信息
            return $this->ajaxFail($file->getError(), ['Content-Type' => 'text/plain']);
        }

    }
}
