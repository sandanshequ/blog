<?php

namespace app\index\model;

use think\Model;

/**
 * 元器件关联目录
 * @package app\index\model
 */
class CategoryModel extends BaseModel
{
    protected $table = 'tbl_categories';

    /**
     * 父级
     */
    public function parent()
    {
        return $this->belongsTo('CategoryModel', 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany('CategoryModel', 'parent_id', 'id');
    }

    public function components()
    {
        return $this->hasMany('ComponentModel', 'category_id', 'id');
    }

    /**
     * 获取分类及元器件数量信息
     * 默认获取所有分类
     * @param null $category_id 一级分类id
     * @param bool $select      是否优选
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCategoryWithNumber($category_id = null, $select = 1)
    {
        $query = $this->field('id,name')
            ->where('parent_id', '');

        if (!empty($category_id))
        {
            $query = $query->where('id', $category_id);
        }

        if ($select == 2)
        {
            return $query
                ->with(['children' => function ($q) {
                    $q->field('id,name,parent_id')
                        ->withCount(['components' => function ($query) {
                            $query->where('supply_status', 2)
                                ->where('disable', null);
                        }]);
                }])
                ->select();
        }

        return $query
            ->with(['children' => function ($q) {
                $q->field('id,name,parent_id')
                    ->withCount(['components' => function ($query) {
                        $query->where('disable', null);
                    }]);
            }])
            ->select();
    }
}
