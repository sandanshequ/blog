<?php

namespace app\index\controller;

use app\index\model\CategoryModel;
use app\index\model\ComponentModel;
use app\index\model\ComponentReviewModel;
use app\index\model\ManufacturerModel;
use app\index\model\RecordModel;
use think\Controller;
use think\Db;
use think\Error;
use think\Exception;
use think\Request;

/**
 * 元器件控制器
 * Class ComponentController
 * @package app\index\controller
 */
class ComponentController extends CommonController
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
        if (in_array(1, $group_ids) || in_array(6, $group_ids))
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
     * 元器件修改
     * @param $sn
     * @return \think\response\View
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
        $this->assign('title', '元器件数据修改');
        return view();
    }

    /**
     * 元器件主页
     */
    public function index()
    {
        $this->assign('title', 'CETC20元器件数据平台');
        return view();
    }

    /**
     * 详情页
     * @param $sn
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($sn)
    {
        if (empty($sn))
        {
            $this->error("非法参数sn");
        }

        // 查询元器件详情
        $model = new ComponentModel();
        $data  = $model->getComponentDetail($sn);

        if (empty($data))
        {
            $this->error('未找到该物资编码对应的元器件');
        }
        if (empty($data) || @$data['disable'] == 1)
        {
            return redirect('/index/search/result_none');
        }

        // 查询分类信息
        $model    = new CategoryModel();
        $category = $model->field('id,name,parent_id')
            ->where('id', $data['category_id'])
            ->with('parent')
            ->find();

        $this->assignRole();
        $this->assign('data', $data);

        $this->assign('title', $data['specification_model_no'] . '--元器件详情');
//                dump($data);exit;
        $this->assign('category', $category);
        return view();
    }

    /**
     * 元器件列表页
     * @param string $key
     * @param int    $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function list(string $key = '', int $page_size = 12)
    {
        $this->setAuthorize(6);
        $model = new ComponentModel();
        $list  = $model->getList($key, $page_size);

        $this->assign("list", $list);
        $this->assign('title', '元器件列表-元器件数据管理');
        $this->assign('nav_name', 'component/index');
        return view();
    }

    /**
     * 审核详情
     * @param null $sn
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function review_detail($sn = null)
    {
        $this->setAuthorize(4);

        if (empty($sn))
        {
            $this->error("非法参数pid");
        }
        $model = new ComponentModel();
        $data  = $model->getComponentDetail($sn);

        // 处理待审核数据格式
        $review           = $data['component_review'];
        $review['params'] = json_decode($review['params_info'], true);
        unset($data['component_review']);
        unset($review['params_info']);

        // 处理files的格式，以type为键
        $files  = [];
        $params = [];
        foreach ($review['files'] as $k => $file)
        {
            $row['name']          = $file['name'];
            $row['path']          = $file['path'];
            $files[$file['type']] = $row;
        }
        // 参数处理
        foreach ($review['params'] as $k => $param)
        {
            $row['param_value']         = $param['param_value'];
            $params[$param['param_id']] = $row;
        }

        unset($review['files'], $review['params']);
        $review['files']  = $files;
        $review['params'] = $params;
        $this->assign('title', '元器件数据修改审核详情');
        $this->assign('component', $data);
        $this->assign('review', $review);


        //    dump($review);
        //      dump($data);
        //      exit;
        return view();
    }


    /**
     * 审核列表
     * @param string $q
     * @param int    $page_size
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function review_list(string $q = '', int $page_size = 12)
    {
        $this->setAuthorize(4);

        $model = new ComponentReviewModel();
        $list  = $model->getList($q, $page_size);
        $this->assign('title', '元器件数据修改审核列表');
        $this->assign("list", $list);
        $this->assign('nav_name', 'component/review_list');
        //                                dump($list->toArray());exit;
        return view();
    }

    /**
     * 新增元器件 
     */ 
    public function add()
    {
        $this->setAuthorize(6);
        // 查询大类数据
        $model = new CategoryModel();

        $category = $model->where('parent_id', '')
            ->field('id,name')
            ->select();
        $this->assign('title', '新增元器件-元器件数据管理');
        $this->assign('category', $category);
        $this->assign('nav_name', 'component/add');
        return view();
    }


    /**
     * 导入数据
     * @return \think\response\View
     */
    public function import()
    {
        $this->setAuthorize(6);
        $this->assign('title', '批量导入-元器件数据管理');
        $this->assign('nav_name', 'component/import');
        return view();
    }


    /**
     * 数据导入处理页面
     * @return array|\think\response\Json|\think\response\View
     */
    public function importShow()
    {
        $file = request()->file('file');
        if (empty($file))
        {
            $this->error('未选择上传的文件');
        }
        $date           = date("Ymd");
        $upload_path    = "./data_file/file/$date/";
        $config['ext']  = 'csv';
        $config['size'] = 10 * 1024 * 1024;

        //创建目录
        if (!is_dir($upload_path))
        {
            mkdir($upload_path, 0777, true);
        }
        $info = $file->validate($config)
            ->rule('md5')
            ->move($upload_path);
        if ($info)
        {
            // 成功上传后 获取上传信息
            $params['filename'] = $info->getFilename();
            $params['filepath'] = $upload_path . $info->getSaveName();
            $components         = [];

            // 写入记录
            $record = new RecordModel();
            $record->recordUpload([
                'review_user_id' => $this->user['id'],
                'user_id'        => $this->user['id'],
                'file_name'      => $params['filename'],
                'file_path'      => $params['filepath'],
                'action_type'    => '管理员修改'
            ]);


            if (file_exists($params['filepath']) && is_file($params['filepath']))
            {
                try
                {
                    $handle = fopen($params['filepath'], 'r');
                    $fields = ['serial_no', 'name', 'specification_model_no', 'quality', 'reliability_quality',
                        'temperature', 'category', 'manufacturer', 'origin', 'package', 'size', 'norm_no', 'supply_status'];
                    $row    = fgetcsv($handle);
                    $number = 1;
                    while ($row)
                    {
                        if ($number > 1 && !empty(@$row[0]))
                        {
                            for ($j = 0; $j < count($row); $j++)
                            {
                                $component[$fields[$j]] = iconv('GB2312', 'UTF-8', $row[$j]);
                            }
                            $components[] = $component;
                        }
                        $row = fgetcsv($handle);
                        $number++;
                    }


                } catch (Exception $e)
                {
                    $this->error($e->getMessage());
                }
            }
        } else
        {
            $this->error($file->getError());

        }
        //                    dump($components);exit;
        $this->assign('title', '批量修改-元器件数据管理');
        $this->assign('list', $components);
        return view();

    }

    /**
     * 批量添加处理页面
     * @return \think\response\Json|\think\response\View
     */
    public function importAddShow()
    {
        $file = request()->file('file');
        if (empty($file))
        {
            $this->error('未选择上传的文件');
        }
        $date           = date("Ymd");
        $upload_path    = "./data_file/file/$date/";
        $config['ext']  = 'csv';
        $config['size'] = 10 * 1024 * 1024;

        //创建目录
        if (!is_dir($upload_path))
        {
            mkdir($upload_path, 0777, true);
        }
        $info = $file->validate($config)
            ->rule('md5')
            ->move($upload_path);
        if ($info)
        {
            // 成功上传后 获取上传信息
            $params['filename'] = $info->getFilename();
            $params['filepath'] = $upload_path . $info->getSaveName();

            // 写入记录
            $record = new RecordModel();
            $record->recordUpload([
                'review_user_id' => $this->user['id'],
                'user_id'        => $this->user['id'],
                'file_name'      => $params['filename'],
                'file_path'      => $params['filepath'],
                'action_type'    => '管理员添加'
            ]);
            $components = [];

            if (file_exists($params['filepath']) && is_file($params['filepath']))
            {
                try
                {
                    $handle = fopen($params['filepath'], 'r');
                    $fields = ['serial_no', 'name', 'specification_model_no', 'quality', 'reliability_quality',
                        'temperature', 'category', 'manufacturer', 'origin', 'package', 'size', 'norm_no', 'supply_status'];
                    $row    = fgetcsv($handle);
                    $number = 1;

                    while ($row)
                    {
                        if ($number > 1 && !empty(@$row[0]))
                        {
                            for ($j = 0; $j < count($row); $j++)
                            {
                                $component[$fields[$j]] = iconv('GB2312', 'UTF-8', $row[$j]);
                            }

                            $components[] = $component;
                        }
                        $row = fgetcsv($handle);
                        $number++;
                    }

                } catch (Exception $e)
                {
                    $this->error($e->getMessage() . $e->getTraceAsString());
                }
            }
        } else
        {
            $this->error($file->getError());

        }
        $this->assign('title', '批量新增-元器件数据管理');
        $this->assign('list', $components);
        return view();
    }


    /**
     * 导入一条元器件信息
     * @param        $serial_no
     * @param string $supply_status
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function importOneComponentAction($serial_no, $supply_status = '')
    {
        if (empty($serial_no))
        {
            return $this->ajaxFail('参数错误sn');
        }

        $pre_key = substr($serial_no, 0, 2);
        if ($pre_key != 'WG' && $pre_key != 'WP')
        {
            return $this->ajaxFail('错误的物资编码格式');
        }
        if (strlen($serial_no) != 10)
        {
            return $this->ajaxFail('错误的物资编码格式');
        }
        if (empty($supply_status))
        {
            return $this->ajaxFail("供货状态不可为空");
        }
        switch ($supply_status)
        {
            case '可用':
                $supply_status = 1;
                break;
            case '优选':
                $supply_status = 2;
                break;
            case '推荐':
                $supply_status = 3;
                break;
            case '限用':
                $supply_status = 4;
                break;
            default:
                return $this->ajaxFail('状态字段错误!');
                break;
        }
        $model   = new ComponentModel();
        $isExist = $model->where('serial_no', $serial_no)->find();
        if (!$isExist)
        {
            return $this->ajaxFail('不存在的物资编码！');
        }
        // 查询其他需要字段
        $db_data = $model->field('pid,category_id')
            ->where('serial_no', $serial_no)
            ->find();

        if (!empty($db_data['category_id']))
        {
            // 更新状态
            $model->save(['supply_status' => $supply_status],
                ['serial_no' => $serial_no]);

            // 搜索引擎更新
            $re = $model->updateFromEs($db_data['category_id'], $serial_no,
                ['supply_status' => $supply_status]);
            if ($re)
            {
                return $this->ajaxSuccess('');
            }
        } else
        {
            return $this->ajaxFail('');
        }


    }

    /**
     * 添加一条元器件信息
     */
    public function addOneComponentAction()
    {
        $params = input('post.');
        // 先查询出来厂商id及分类id
        $mname = $params['manufacturer'];
        $cname = $params['category'];
        if (empty($mname) || empty($cname))
        {
            return $this->ajaxFail('厂商或分类不正确');
        }

        $serial_no = input('post.serial_no');

        if (empty($serial_no))
        {
            return $this->ajaxFail('错误的物资编码!');
        }
        if (empty($params['name']))
        {
            return $this->ajaxFail('元器件名称不可为空!');
        }

        $pre_key = substr($serial_no, 0, 2);
        if ($pre_key != 'WG' && $pre_key != 'WP')
        {
            return $this->ajaxFail('错误的物资编码格式');
        }
        if (strlen($serial_no) != 10)
        {
            return $this->ajaxFail('错误的物资编码格式');
        }

        $model   = new ComponentModel();
        $isExist = $model->where('serial_no', $serial_no)->find();
        if ($isExist)
        {
            return $this->ajaxFail('已存在该元器件！');
        }

        $model = new CategoryModel();
        $cid   = $model->where('name', $cname)->find();
        $model = new ManufacturerModel();
        $mid   = $model->where('name', $mname)->find();

        if (empty($cid) || empty($mid))
        {
            return $this->ajaxFail('未找到对应的厂商或分类！');
        }
        // 处理字段
        switch (@$params['supply_status'])
        {
            case '可用':
                @$params['supply_status'] = 1;
                break;
            case '优选':
                @$params['supply_status'] = 2;
                break;
            case '推荐':
                @$params['supply_status'] = 3;
                break;
            case '限用':
                @$params['supply_status'] = 4;
                break;
        }

        $temperature = @$params['temperature'];
        if (!empty($temperature) && strpos($temperature, '~'))
        {
            $params['temperature_low']  = @explode('~', $temperature)[0];
            $params['temperature_high'] = @explode('~', $temperature)[1];
            unset($params['temperature']);
        }

        //调用接口
        $re = $this->addData('/component', [
            'name'                   => @$params['name'],
            'serial_no'              => $params['serial_no'],
            'supply_status'          => @$params['supply_status'] ?? 2,
            'category_id'            => $cid['id'],
            'specification_model_no' => @$params['specification_model_no'],
            'quality'                => @$params['quality'],
            'reliability_quality'    => @$params['reliability_quality'],
            'manufacturer_id'        => $mid['id'],
            'manufacturer_name'      => $params['manufacturer'],
            'origin'                 => @$params['origin'],
            'package'                => @$params['package'],
            'norm_no'                => @$params['norm_no'],
            'disable'                => null,
            'temperature_low'        => @$params['temperature_low'],
            'temperature_high'       => @$params['temperature_high']

        ]);
        if ($re)
        {
            return $this->ajaxSuccess($re);
        }
        return $this->ajaxFail($re);
    }

    /**
     * 上传导入的文件
     * @return \think\response\Json
     */
    public
    function uploadImportFile()
    {

        $file = request()->file('file');
        if (empty($file))
        {
            return $this->ajaxFail('未选择上传的文件');
        }
        $date = date("Ymd");

        $upload_path    = "./data_file/file/$date/";
        $config['ext']  = 'csv,xls,xlsx';
        $config['size'] = 10 * 1024 * 1024;

        //创建目录
        if (!is_dir($upload_path))
        {
            mkdir($upload_path, 0777, true);
        }

        $info = $file->validate($config)
            ->rule('md5')
            ->move($upload_path);
        if ($info)
        {
            // 成功上传后 获取上传信息
            $params['filename'] = $info->getFilename();
            $params['filepath'] = $upload_path . $info->getSaveName();

            if (file_exists($params['filepath']) && is_file($params['filepath']))
            {
                try
                {
                    $handle     = fopen($params['filepath'], 'r');
                    $components = [];
                    $fields     = ['serial_no', 'name', 'specification_model_no', 'quality', 'reliability_quality',
                        'temperature', 'manufacturer', 'origin', 'package', 'size', 'norm_no', 'supply_status'];
                    $row        = fgetcsv($handle);
                    $number     = 1;
                    while ($row)
                    {
                        if ($number > 1 && !empty(@$row[0]))
                        {
                            for ($j = 0; $j < count($row); $j++)
                            {
                                $component[$fields[$j]] = iconv('GB2312', 'UTF-8', $row[$j]);
                            }

                            // 处理字段
                            switch (@$component['supply_status'])
                            {
                                case '可用':
                                    @$component['supply_status'] = 1;
                                    break;
                                case '优选':
                                    @$component['supply_status'] = 2;
                                    break;
                                case '推荐':
                                    @$component['supply_status'] = 3;
                                    break;
                                case '限用':
                                    @$component['supply_status'] = 4;
                                    break;
                            }

                            $temperature = @$component['temperature'];
                            if (!empty($temperature) && strpos($temperature, '~'))
                            {
                                $component['temperature_low']  = @explode('~', $temperature)[0];
                                $component['temperature_high'] = @explode('~', $temperature)[1];
                                unset($component['temperature'], $component['manufacturer']);

                            }

                            // 查询其他需要字段
                            $model   = new ComponentModel();
                            $db_data = $model->field('pid,category_id')
                                ->where('serial_no', $component['serial_no'])
                                ->find();

                            if (!empty($db_data['category_id']))
                            {
                                // 更新状态
                                $model->save(['supply_status' => $component['supply_status']],
                                    ['serial_no' => $component['serial_no']]);

                                // 搜索引擎更新
                                $model->updateFromEs($db_data['category_id'], $component['serial_no'],
                                    ['supply_status' => $component['supply_status']]);
                            }

                            $components[] = $db_data;

                        }
                        $row = fgetcsv($handle);
                        $number++;
                    }
                    return $this->ajaxSuccess($components, ['Content-Type' => 'text/plain']);
                } catch (Exception $e)
                {
                    return $this->ajaxFail($e->getMessage(), ['Content-Type' => 'text/plain']);
                }
            }
        } else
        {
            // 上传失败获取错误信息
            return $this->ajaxFail($file->getError(), ['Content-Type' => 'text/plain']);
        }
    }


    /**
     * 添加元器件
     */
    public
    function addAction()
    {
        $params = input('post.');
        // 先查询出来厂商id及分类id
        if (empty($params['manufacturer_id']) || empty($params['category_id']))
        {
            return $this->ajaxFail('厂商或分类不正确');
        }

        $serial_no = input('post.serial_no');
        $pre_key   = substr($serial_no, 0, 2);

        if ($pre_key != 'WG' && $pre_key != 'WP')
        {
            return $this->ajaxFail('错误的物资编码格式');
        }

        if (strlen($serial_no) != 10)
        {
            return $this->ajaxFail('错误的物资编码格式');
        }

        if (empty($params['name']))
        {
            return $this->ajaxFail('元器件名称不可为空');
        }

        $model   = new ComponentModel();
        $isExist = $model->where('serial_no', $serial_no)->find();
        if ($isExist)
        {
            return $this->ajaxFail('已存在该元器件！');
        }

        //调用接口
        $re = $this->addData('/component', [
            'name'                   => @$params['name'],
            'serial_no'              => $params['serial_no'],
            'supply_status'          => @$params['supply_status'],
            'category_id'            => $params['category_id'],
            'specification_model_no' => @$params['specification_model_no'],
            'quality'                => @$params['quality'],
            'reliability_quality'    => @$params['reliability_quality'],
            'manufacturer_id'        => $params['manufacturer_id'],
            'manufacturer_name'      => @$params['manufacturer_name'],
            'origin'                 => @$params['origin'],
            'package'                => @$params['package'],
            'norm_no'                => @$params['norm_no'],
            'disable'                => null,
            'temperature_low'        => @$params['temperature_low'],
            'temperature_high'       => @$params['temperature_high']

        ]);
        if ($re)
        {
            return $this->ajaxSuccess($re);
        }
        return $this->ajaxFail($re);
    }


    /**
     * 获取所有厂商列表
     */
    public
    function getManufacturersAction()
    {
        $model = new ManufacturerModel();
        $data  = $model->field('id, name, full_name')
            ->select();
        return $this->ajaxSuccess($data);
    }


    /**
     * 获取二级分类列表
     * @param $category_id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public
    function getSubCategoriesAction($category_id = null)
    {
        if (empty($category_id))
        {
            return $this->ajaxFail('参数错误:category_id');
        }
        $model = new CategoryModel();
        $data  = $model->where('parent_id', $category_id)
            ->field('id,name,parent_id')
            ->select();
        return $this->ajaxSuccess($data);
    }


    /**
     * 导出所有元器件数据
     */
    public
    function exportAllComponentAction()
    {
        $model = new ComponentModel();

        $data = $model->field('serial_no,name,specification_model_no,quality,reliability_quality,' .
            'temperature_low,temperature_high,category_id,manufacturer_id,origin,package,size,norm_no,supply_status')
            ->with(['manufacturer' => function ($query) {
                $query->field('id,name');
            }])
            ->with(['category' => function ($query) {
                $query->field('id,name');
            }])
            ->select()
            ->each(function ($component) {
                $component['manufacturer_name'] = $component['manufacturer']['name'];
                $component['category_name']     = $component['category']['name'];
                $component['temperature']       = $component['temperature_low'] . ' ~ ' . $component['temperature_high'];
                switch ($component['supply_status'])
                {
                    case 1:
                        $component['supply_status'] = '可用';
                        break;
                    case 2:
                        $component['supply_status'] = '优选';
                        break;
                    case 3:
                        $component['supply_status'] = '推荐';
                        break;
                    case 4:
                        $component['supply_status'] = '限用';
                        break;
                }

                unset($component['manufacturer'], $component['category'],
                    $component['temperature_low'], $component['temperature_high']);

            })
            ->toArray();

        // 整理排序

        $tableHeader = '物资编码,元器件名称,规格型号,质量等级,可靠性预计,工作温度范围,分类,生产厂商,国产/进口,封装形式,封装尺寸,规范号,供货状态';
        $filename    = date("YmdHi");
        $file        = fopen('./output/components' . $filename . '.csv', 'w');

        fputcsv($file, explode(',', iconv('UTF-8', "GB2312//IGNORE", $tableHeader)));
        foreach ($data as $key => $component)
        {
            // 重新赋值排序
            $row['serial_no']              = $component['serial_no'];
            $row['name']                   = $component['name'];
            $row['specification_model_no'] = $component['specification_model_no'];
            $row['quality']                = $component['quality'];
            $row['reliability_quality']    = $component['reliability_quality'];
            $row['temperature']            = $component['temperature'];
            $row['category_name']          = $component['category_name'];
            $row['manufacturer_name']      = $component['manufacturer_name'];
            $row['origin']                 = $component['origin'];
            $row['package']                = $component['package'];
            $row['size']                   = $component['size'];
            $row['norm_no']                = $component['norm_no'];
            $row['supply_status']          = $component['supply_status'];

            foreach ($row as $k => $value)
            {
                //                $component[$k] = mb_convert_encoding($value, 'GB2312', 'UTF - 8');
                $row[$k] = iconv("UTF-8", "GB2312//IGNORE", $value);
            }
            fputcsv($file, array_values($row));
        }

        fclose($file);
        return redirect('/output/components' . $filename . '.csv');
    }

    /**
     * 上传元器件附件
     * @return \think\response\Json
     */
    public
    function uploadFileAction()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file          = request()->file('file');
        $type          = request()->post('type');
        $component_pid = request()->post('component_pid');

        if (empty($file))
        {
            return $this->ajaxFail('未选择上传的文件');
        }


        if (empty($type) || empty($component_pid))
        {
            return $this->ajaxFail("缺少参数");
        }
        $typename = "datasheet";
        switch ($type)
        {
            case "1":
                $typename = 'datasheet';
                break;
            case "4":
                $typename = 'product_pic';
                break;
            case "6":
                $typename = 'standard';
                break;
            case "7":
                $typename = 'size';
                break;
            default:
                $typename = 'file';
                break;
        }
        $date = date("Ymd");

        $upload_path = "./data_file/$typename/$date/";
        //        $config['ext']  = 'jpg,jpeg,png,gif,csv,txt,xls,xlsx,bmp,pdf';
        $config['size'] = 30 * 1024 * 1024;

        //创建目录
        if (!is_dir($upload_path))
        {
            mkdir($upload_path, 0777, true);
        }

        $info = $file->validate($config)
            ->rule('md5')
            ->move($upload_path);
        if ($info)
        {
            // 成功上传后 获取上传信息
            $params['component_pid'] = $component_pid;
            $params['name']          = $info->getFilename();
            $params['path']          = "data_file/$typename/$date/" . $info->getSaveName();
            $params['type']          = $type;
            $this->assignRole();
            if ($this->role == 'admin')
            {
                $re = $this->updateData('component/file', $params);
            } else
            {
                $re = $this->updateData('component_review/file', $params);
            }

            if ($re['status'] == 1)
            {
                return $this->ajaxSuccess(['file_path' => $params['path'],
                                           'orig_name' => $params['name']],
                    ['Content-Type' => 'text/plain']);
            } else
            {
                return $this->ajaxFail('保存文件信息失败', ['Content-Type' => 'text/plain']);
            }
        } else
        {
            // 上传失败获取错误信息
            return $this->ajaxFail($file->getError(), ['Content-Type' => 'text/plain']);
        }
    }

    /**
     * 元器件审核功能
     * 文档:open-docs.cissdata.com:2048/cetcjie-kou-wen-dang/3-yuan-qi-jian-guan-li-jie-kou.html#b137fb3ec0ae43fc15bc1fe179e893f0
     */
    public
    function reviewAction()
    {
        $id                       = input('post.id');
        $status                   = input('post.status');
        $params                   = input('post.');
        $params['review_user_id'] = $this->user['id'];

        if (empty($id))
        {
            return $this->ajaxFail("no id");
        }
        if (empty($status))
        {
            return $this->ajaxFail("no status");
        }
        $re = $this->addData('/component_review/review/' . $id, $params);

        return $this->ajaxSuccess($re['data']);
    }

    /**
     * 修改元器件
     */
    public
    function editAction()
    {
        $params            = input('post.');
        $params['user_id'] = $this->user['id'];
        if (empty($params['id']) || empty($params['category_id']) || empty($params['manufacturer_id']))
        {
            return $this->ajaxFail('缺少参数[id,category_id,manufacturer_id]');
        }
        if (empty($params['name']))
        {
            return $this->ajaxFail('元器件名称不可为空');
        }


        $this->assignRole();
        if ($this->role == 'admin')
        {
            $re = $this->updateData('/component/sn/', $params);
        } else
        {
            $re = $this->updateData('/component_review/sn/', $params);
        }
        return $this->ajaxSuccess($re['data']);
    }


    /**
     * 停用元器件
     * @param $pid
     * @throws \think\exception\DbException
     */
    public
    function deleteAction($pid)
    {
        if (empty($pid))
        {
            $this->error('非法操作');
        }
        $model = new ComponentModel();

        $result = $model->del($pid);
        if ($result)
            $this->success('停用成功', '/index/component/list', null, 1);
        $this->error('停用失败');
    }


    /**
     * 批量停用
     * @param array $pids 元器件pid数据
     * @return \think\response\Json
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public
    function deleteMultiAction($pids = [])
    {
        if (empty($pids))
        {
            return $this->ajaxFail('参数错误pids[]');
        }
        Db::name('tbl_components')->whereIn('pid', $pids)
            ->data(['disable' => 1])
            ->update();
        return $this->ajaxSuccess('操作成功');

    }

    /**
     * 还原元器件
     * @param $pid
     * @throws \think\exception\DbException
     */
    public
    function restoreAction($pid)
    {
        $model              = new ComponentModel();
        $component          = $model->find($pid);
        $component->disable = null;
        $result             = $component->save();
        if ($result)
            $this->success('还原成功', null, null, 1);
        $this->error('还原失败');
    }
}
