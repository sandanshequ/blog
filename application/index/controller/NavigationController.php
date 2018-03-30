<?php

namespace app\index\controller;

use app\index\model\CategoryModel;
use app\index\model\ComponentModel;

class NavigationController extends CommonController
{

    function initialize()
    {
        parent::initialize();
    }

    /**
     * 分类导航首页
     * @param null $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($id = null)
    {
        // 查询 所有分类及分类一数量信息
        $model = new CategoryModel();
        $list  = $model->getCategoryWithNumber($id);


        // 去除分类下无数据的分类，计算总数
        foreach ($list as $key => $category)
        {
            $sum = 0;

            foreach ($category['children'] as $k => $v)
            {
                if ($v['components_count'] < 1)
                {
                    unset($list[$key]['children'][$k]);
                } else
                {
                    $sum += $v['components_count'];
                }
            }
            $list[$key]['sum'] = $sum;
            if ($sum == 0)
            {
                unset($list[$key]);
            }
        }

        $this->assign('list', $list);
        $this->assign('title', '分类导航-CETC20元器件数据平台');
        return view();
    }

    /**
     * 分类列表页
     * @param     $category_id
     * @param int $page
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function list($category_id, $page = 1)
    {
        // 查询分类信息
        if (empty($category_id))
        {
            $this->error("参数错误:category_id");
        }

        $model    = new CategoryModel();
        $category = $model->field('id,name,parent_id')
            ->where('id', $category_id)
            ->withCount(['components' => function ($query) {
                $query->where('disable', null);
            }])
            ->with('parent')
            ->find();

        $model   = new ComponentModel();
        $filters = $this->filter_params();

        $all_query_params              = input('get.');
        $all_query_params['page_size'] = 10;
        $all_query_params['page']      = $page;

        // 处理特殊字段
        $temperature = input('get.temperature');
        if (strpos($temperature, ','))
        {
            $all_query_params['low']  = trim(explode(',', $temperature)[0]);
            $all_query_params['high'] = trim(explode(',', $temperature)[1]);
        }

        foreach ($all_query_params as $key => $all_query_param)
        {
            if (empty($all_query_param))
            {
                unset($all_query_params[$key]);
            }
        }

        //筛选
        $base_url = '/component/list_search/' . $category_id;
        $response = json_decode($this->client->request('POST', $base_url, ['query' => $all_query_params])->getBody(), true);


        // 根据搜索筛选的pids再进行查询 ，找出没有被停用的元器件
        if (!empty($response['data']))
        {
            //            $response['total'] = $category['components_count'];
            $page = $model->paginate(10, $response['total'], ['query' => $all_query_params])->render();
        }


        $this->assign('category', $category);
        $this->assign('filters', $filters);
        $this->assign('page', $page);
        $this->assign('list', $response);
        $this->assign('title', '分类列表-CETC20元器件数据平台');
        return view();
    }

    /**
     * 分类导航首页
     * @param null $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function select($id = null)
    {
        // 查询 所有分类及分类一数量信息
        $model = new CategoryModel();
        $list  = $model->getCategoryWithNumber($id, 2);

        // 去除分类下无数据的分类，计算总数
        foreach ($list as $key => $category)
        {
            $sum = 0;

            foreach ($category['children'] as $k => $v)
            {
                if ($v['components_count'] < 1)
                {
                    unset($list[$key]['children'][$k]);
                } else
                {
                    $sum += $v['components_count'];
                }
            }
            $list[$key]['sum'] = $sum;
            if ($sum == 0)
            {
                unset($list[$key]);
            }
        }

        $this->assign('list', $list);
        $this->assign('title', '优品目录-CETC20元器件数据平台');
        return view();
    }


    /**
     * 分类列表页
     * @param     $category_id
     * @param int $page
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function select_list($category_id, $page = 1)
    {
        // 查询分类信息
        if (empty($category_id))
        {
            $this->error("参数错误:category_id");
        }

        $model    = new CategoryModel();
        $category = $model->field('id,name,parent_id')
            ->where('id', $category_id)
            ->withCount(['components' => function ($query) {
                $query->where('disable', null)->where('supply_status', 2);
            }])
            ->with('parent')
            ->find();

        $model   = new ComponentModel();
        $filters = $this->filter_params();

        $all_query_params                  = input('get.');
        $all_query_params['page_size']     = 10;
        $all_query_params['supply_status'] = 2;
        $all_query_params['page']          = $page;

        // 处理特殊字段
        $temperature = input('get.temperature');
        if (strpos($temperature, ','))
        {
            $all_query_params['low']  = trim(explode(',', $temperature)[0]);
            $all_query_params['high'] = trim(explode(',', $temperature)[1]);
        }

        foreach ($all_query_params as $key => $all_query_param)
        {
            if (empty($all_query_param))
            {
                unset($all_query_params[$key]);
            }
        }

        //筛选
        $base_url = '/component/list_search/' . $category_id;
        $response = json_decode($this->client->request('POST', $base_url, ['query' => $all_query_params])->getBody(), true);

        // 根据搜索筛选的pids再进行查询 ，找出没有被停用的元器件
        if (!empty($response['data']))
        {
            //            $response['total'] = $category['components_count'];
            $page = $model->paginate(10, $response['total'], ['query' => $all_query_params])->render();
        }


        $this->assign('category', $category);
        $this->assign('filters', $filters);
        $this->assign('page', $page);
        $this->assign('list', $response);

        //        dump($filters);exit;
        $this->assign('title', '优品目录元器件列表-CETC20元器件数据平台');
        return view();
    }


    public function filter_params()
    {
        $input                    = input('get.');
        $input['package']         = isset($input['package']) ? $input['package'] : '';
        $input['quality']         = isset($input['quality']) ? $input['quality'] : '';
        $input['manufacturer_id'] = isset($input['manufacturer_id']) ? $input['manufacturer_id'] : 0;
        $category_id              = isset($input['category_id']) ? intval($input['category_id']) : 0;

        if (empty($category_id))
        {
            $this->error('参数错误');
        }

        //分类所有参数
        $url    = '/component/filter/' . $category_id;
        $params = $this->getData($url, []);


        $extend_params = $params['extend_filter'];
        $manufacturers = array_column($params['manufacturers'], 'name', 'id');
        $extend_id     = array_column($extend_params, 'name', 'id');
        $extend_params = null;
        //基本参数
        $basic_params = [
            'package', 'quality', 'manufacturer_id',
            'origin', 'selected_categories',
            'temperature_low', 'temperature_high'
        ];
        //扩展参数id
        $ids        = array_keys($extend_id);
        $all_params = array_merge($ids, $basic_params);
        foreach ($all_params as $k => $val)
        {
            if (is_numeric($val))
            {
                $val           = 'param_' . $val;
                $filters[$val] = empty($input[$val]) ? 0 : $input[$val];
            } else if ($val == 'temperature_low' || $val == 'temperature_high')
            {
                $temp          = substr($val, 12);
                $filters[$val] = empty($input[$temp]) ? '' : urldecode($input[$temp]);
            } else
            {
                $filters[$val] = empty($input[$val]) ? '' : $input[$val];
            }
        }
        $filters['column'] = empty($input['column']) ? '' : $input['column'];
        $filters['key']    = empty($input['key']) ? '' : $input['key'];

        $base_url = '/component/valid_search/' . $category_id;
        $params   = [
            "form_params" => json_encode($filters)
        ];


        $response = $this->client
            ->request('POST', $base_url, ['form_params' => $params])
            ->getBody();

        $response = json_decode($response, true);

        if (empty($response))
        {
            return [];
        }

        //拼接扩展参数
        foreach ($response as $k => $val)
        {
            if (strpos($k, 'param_') !== false)
            {
                $id                         = substr($k, 6);
                $response['extend_param'][] = [
                    'id'     => $k,
                    'name'   => $extend_id[$id],
                    'values' => $val
                ];
                unset($response[$k]);
            }
        };
        //拼接厂商数据
        //        dump($response);exit;

        if (!empty($response['manufacturer_id']))
        {
            foreach (@$response['manufacturer_id'] as $key => $id)
            {
                if (!empty(@$manufacturers[$id['key']]))
                {
                    $response['manufacturer_id'][$key]['name'] = @$manufacturers[$id['key']];
                }
            }
        }

        return $response;
    }
}
