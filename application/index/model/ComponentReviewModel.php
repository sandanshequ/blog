<?php

namespace app\index\model;

use think\Model;

class ComponentReviewModel extends BaseModel
{

    protected $table = 'tbl_components_review';

    public function files()
    {
        return $this->hasMany('ComponentReviewFileModel', 'component_pid', 'pid');
    }

    public function manufacturer()
    {
        return $this->belongsTo('ManufacturerModel', 'manufacturer_id', 'id');
    }

    public function component()
    {
        return $this->belongsTo('ComponentModel', 'serial_no', 'serial_no');
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

        $query = $this->with('manufacturer')
            ->order('created_at', 'desc');

        if (!empty($q))
        {
            $query = $query->where('serial_no', 'like', "$q%")
                ->whereOr('specification_model_no', 'like', "$q%");
        }
        return $query->paginate($page_size)->each(function ($item, $key) {

            // 供货状态
            switch ($item['supply_status'])
            {
                case    0:
                    break;
                case    1:
                    $item['supply_status'] = '可用';
                    break;
                case    2:
                    $item['supply_status'] = '优选';
                    break;
            }

        });
    }

}
