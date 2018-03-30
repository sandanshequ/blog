<?php

namespace app\index\controller;

use app\index\model\ComponentModel;
use app\index\model\RecordModel;
use think\Controller;
use think\Request;

/**
 * 审计
 * Class AuditController
 * @package app\index\controller
 */
class AuditController extends CommonController
{

    function initialize()
    {
        parent::initialize();
        $this->setAuthorize(2);

    }
    /**
     * 元器件审计记录
     * @param int  $page_size
     * @param null $key
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function component($page_size = 10, $key = null)
    {
        $model = new RecordModel();
        $list  = $model->getRecordsByType(RecordModel::TYPE_COMPONENT, $page_size, $key);

        $this->assign("list", $list);
        $this->assign('title', '元器件修改日志-审计');
        $this->assign('nav_name', 'audit/component');

        return view();
    }


    /**
     * eda审计记录
     * @param int  $page_size
     * @param null $key
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function eda($page_size = 20, $key = null)
    {
        $model = new RecordModel();
        $list  = $model->getRecordsByType(RecordModel::TYPE_EDA, $page_size, $key);
        $this->assign("list", $list);
        $this->assign('title', 'EDA修改日志-审计');
        $this->assign('nav_name', 'audit/eda');

        return view();
    }

    /**
     * 批量上传的审计记录
     * @param int $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function multi_upload($page_size = 10)
    {
        $model = new RecordModel();
        $list  = $model->getRecordsByType(RecordModel::TYPE_UPLOAD, $page_size);

        $this->assign("list", $list);
        $this->assign('title', '批量上传日志-审计');
        $this->assign('nav_name', 'audit/multi_upload');

        return view();
    }

}
