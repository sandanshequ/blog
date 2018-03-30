<?php

namespace app\index\model;

use think\Db;
use think\Model;
use think\model\concern\SoftDelete;

class ComponentModel extends BaseModel
{
    protected $table = 'tbl_components';
    //    protected $deleteTime = 'deleted_at';
    protected $pk = 'pid';

    //    protected $type = ['deleted_at' => 'datetime'];

    public function files()
    {
        return $this->hasMany('ComponentFileModel', 'component_pid', 'pid');
    }

    public function manufacturer()
    {
        return $this->belongsTo('ManufacturerModel', 'manufacturer_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo('CategoryModel', 'category_id', 'id');
    }

    /**
     * @return \think\model\relation\HasOne
     */
    public function componentReview()
    {
        return $this->hasOne('ComponentReviewModel', 'serial_no', 'serial_no');
    }

    public function edaReview()
    {
        return $this->hasOne('EdaReviewModel', 'component_sn', 'serial_no');
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
        $query = $this->with(['files' => function ($query) {
            return $query->where('type', 1);
        }, 'manufacturer'])
            ->order(['updated_at' => 'desc', 'created_at']);

        if (!empty($q))
        {
            $query = $query->where('serial_no', 'like', "$q%")
                ->whereOr('specification_model_no', 'like', "$q%");
        }
        return $query->paginate($page_size)->each(function ($item, $key) {
            // 处理关联datasheet
            if (count($item['files']) > 0)
            {
                $item['datasheet'] = $item['files'][0];
            } else
            {
                $item['datasheet'] = null;
            }
            unset($item['files']);
            // 供货状态
            switch ($item['supply_status'])
            {
                case    0:
                    $item['supply_status'] = '无';
                    break;
                case    1:
                    $item['supply_status'] = '可用';
                    break;
                case    2:
                    $item['supply_status'] = '优选';
                    break;
                case    3:
                    $item['supply_status'] = '推荐';
                    break;
                case    4:
                    $item['supply_status'] = '限用';
                    break;
            }

        });
    }

    /**
     * 获取元器件详情信息
     * @param $sn string 物资编码
     * @return array|null|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getComponentDetail($sn)
    {
        if (empty($sn))
            return [];
        $component = $this
            ->where('serial_no', $sn)
            ->with(['files', 'category.parent', 'manufacturer', 'edaReview',
                'componentReview' => function ($query) {
                    $query->with(['files', 'manufacturer']);
                }])
            ->find();

        if (empty($component))
        {
            return null;
        }

        $category_id = $component['category_id'];
        $pid         = $component['pid'];

        if (!empty($category_id))
        {
            $params_table_name = 'tbl_component_param_value_' . $category_id;

            // 查询关联的属性信息
            $component_params = Db::view($params_table_name, 'param_value,param_id')
                ->view('tbl_params', 'name', "tbl_params.id=$params_table_name.param_id")
                ->where($params_table_name . '.component_pid', $pid)
                ->select();

            $component['params'] = $component_params;
        } else
        {
            $component['params'] = [];
        }

        $files  = [];
        $params = [];

        // 处理files的格式，以type为键
        if (!empty($component['files']))
        {
            foreach ($component['files'] as $k => $file)
            {
                $row['name']          = $file['name'];
                $row['path']          = $file['path'];
                $files[$file['type']] = $row;
            }
        }

        unset($row);
        // 参数处理

        if (!empty($component['params']))
        {
            foreach ($component['params'] as $k => $param)
            {
                $row['param_value']         = $param['param_value'];
                $row['name']                = $param['name'];
                $params[$param['param_id']] = $row;
            }
        }

        unset($component['files'], $component['params']);
        $component['files']  = $files;
        $component['params'] = $params;

        return $component;
    }

    /**
     * 根据子类获取元器件列表
     * @param      $id
     * @param int  $page_size
     * @param bool $select
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public function getListByCategory($id, $page_size = 12, $select = false)
    {
        $query = $this->where('category_id', $id)
            ->order('created_at', 'desc')
            ->with(['files', 'manufacturer']);
        if ($select)
        {
            $query = $query->where('supply_status', 2);
        }

        return $query->paginate($page_size, false,
            ['query' =>
                 ['category_id' => $id]
            ]);
    }

    /**
     * 删除
     * @param $pid
     * @return array|\PDOStatement|string|Model
     * @throws \think\exception\DbException
     */
    public function del($pid)
    {
        $component          = $this->find($pid);
        $component->disable = 1;
        $component->save();
        return $component;
    }

    /**
     * 从搜索引擎中获取元器件内容
     * @param $category_id
     * @param $sn
     * @return mixed
     */
    public function getFromEs($category_id, $sn)
    {
        $es             = new \ES();
        $params['id']   = $sn;
        $params['type'] = $category_id;
        $re             = $es->get($params);
        return $re['_source'];
    }


    /**
     * 从搜索引擎中获取元器件内容
     * @param $category_id
     * @param $sn
     * @return mixed
     */
    public function updateFromEs($category_id, $sn, $data = [])
    {
        $es                    = new \ES();
        $params['id']          = $sn;
        $params['type']        = $category_id;
        $params['body']['doc'] = $data;
        $re                    = $es->update($params);
        return $re;
    }
}
