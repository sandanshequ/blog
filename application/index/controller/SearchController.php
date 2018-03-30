<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

/**
 * 搜索控制器
 * Class SearchController
 * @package app\index\controller
 */
class SearchController extends CommonController
{
    function initialize()
    {
        parent::initialize();
    }


    /**
     * 处理搜索类型
     * @param null $q
     * @param null $column
     * @return \think\response\Redirect
     */
    public function index($q = null, $column = null)
    {
        if (empty($q))
        {
            $this->redirect('/index');
        }
        $pre_key  = substr($q, 0, 2);
        $suff_key = substr($q, 2);

        $param['key']    = $q;
        $param['column'] = $column;
        // 判断是否为物资编码
        if ($pre_key === 'WG' || $pre_key === 'WP')
        {
            if (strlen($suff_key) === 8)
            {
                $param['column'] = 'serial_no';
            }
        }
        $response = $this->client->request('GET', '/search/total', ['query' => $param]);
        $result   = json_decode($response->getBody(), true);

        // 无结果
        if (empty($result['id']))
        {
            $data['type'] = 0;
            $data['key']  = $q;
            $this->redirect('/index/search/result_none?q=' . $q);
        }
        // 唯一匹配直接跳转到详情
        if (count($result['id']) == 1)
        {
            $detail_param['category_id'] = $result['category_id'][0]['key'];
            $id                          = $result['id'][0]['key'];

            $url                   = '/component/detail/' . $id;
            $params['category_id'] = $detail_param['category_id'];
            $detail                = $this->getData($url, $params);

            if (isset($detail['errCode']))
            {
                $this->error($detail['errCode'], $detail['msg']);
            }
            $data['info'] = $detail;
            $data['type'] = 1;
            return redirect('/index/component/detail?sn=' . $detail['serial_no']);

        } // 匹配到唯一分类在内容
        elseif (count($result['category_id']) == 1)
        {
            $param['page']      = 1;
            $param['page_size'] = 20;
            $param['loose']     = 0;
            $param['category']  = $result['category_id'][0]['key'];

            $base_url = '/component/list_search/' . $param['category'];
            //参数
            $all_query_params              = $param;
            $all_query_params['page_size'] = 20;
            $all_query_params['page']      = empty($param['page']) ? 1 : $param['page'];
            if (isset($input['key']))
            {
                $all_query_params['key'] = strip_tags($param['key']);
            }
            $response = $this->addData($base_url, $all_query_params);

            if (isset($response['error']))
            {
                return redirect('result_none');
            }
            $data              = $response['data'];
            $data['total_row'] = $response['total'];

            $total_row = $data['total_row'];
            unset($data['total_row']);

            foreach ($data as $k => $val)
            {
                if (!isset($val['datasheet_id']))
                {
                    $val['datasheet_id'] = 0;
                }
                $list[] = $val;
            }
            $data               = [];
            $data['components'] = $list;
            $data['total_row']  = $total_row;
            $data['type']       = 2;
            //分类导航
            $nav_category = $this->getData('/category/' . $param['category']);

            $result = [
                'id'     => $nav_category['id'],
                'name'   => $nav_category['name'],
                'parent' => [
                    'id'   => $nav_category['parent']['id'],
                    'name' => $nav_category['parent']['name'],
                ]
            ];

            $data['navcation'] = $result;

            $this->redirect("/index/navigation/list?column=$column&key=$q&category_id=$result[id]");

        } else
        {
            //分类筛选页
            $param['keyword'] = $q;

            $data = $this->getData('/search/cate', $param);
            if (!$data['total'] && stripos($param['keyword'], 'uF') !== false)
            {
                $param['keyword'] = str_ireplace('uF', 'μF', $param['keyword']);
                $data             = $this->getData('/search/cate', $param);
            }
            if (isset($data['error']))
            {
                return redirect('result_none');
            }
            $data['type'] = 3;
            //            $this->success($data);

            session('temp_search_result', $data);
            session('temp_search_key', $q);
            return redirect('result_category');
        }
    }

    /**
     * 搜索目录页
     * @return \think\response\View
     */
    public function result_category()
    {
        $result = session('temp_search_result');
        $q      = session('temp_search_key');
        //        session('temp_result', null);

        $this->assign('list', $result);
        $this->assign('search_key', $q);
        $this->assign('title', '搜索-CETC20元器件数据平台');
        return view();
    }


    /**
     * 搜索列表页
     */
    public function result_list()
    {

    }

    /**
     * 搜索无结果页面
     * @return \think\response\View
     */
    public function result_none()
    { 
        $this->assign('title', '搜索无结果-CETC20元器件数据平台');
        return view();
    }

    /**
     * 搜索建议
     */
    function search_suggest()
    {
        $keyword                  = input('get.key');
        $keyword                  = strip_tags($keyword);
        $request_param['keyword'] = $keyword;
        //发送请求
        $response = $this->client->request('GET', '/search/suggest', ['query' => $request_param]);
        $result   = json_decode($response->getBody(), true);

        return $this->ajaxSuccess($result);
    }
}
