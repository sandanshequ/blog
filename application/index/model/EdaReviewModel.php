<?php

namespace app\index\model;

use think\Model;

class EdaReviewModel extends BaseModel
{

    protected $table = 'tbl_eda_review';


    public function component()
    {
        return $this->belongsTo('ComponentModel', 'serial_no', 'component_sn');
    }


    public function getReasonAttr($value)
    {
        if (empty($value))
        {
            return "";
        }
        return $value;
    }

    /**
     * 分页列表查询
     * @param     $q
     * @param int $page_size
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getList($q, $page_size = 12)
    {
        if (!empty($q))
        {
            return $this->where('component_sn', 'like', "$q%")
                ->order('created_at', 'desc')
                ->paginate($page_size);
        }
        return $this
            ->order('created_at', 'desc')
            ->paginate($page_size);
    }

}
