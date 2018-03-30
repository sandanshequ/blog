<?php

namespace app\index\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 审计记录
 * Class RecordModel
 * @package app\index\model
 */
class RecordModel extends BaseModel
{
    /**
     * 元器件
     */
    const TYPE_COMPONENT = 'component';
    /**
     * EDA
     */
    const TYPE_EDA = 'eda';
    /**
     * 批量上传
     */
    const TYPE_UPLOAD = 'upload';

    protected $table = 'tbl_components_record';


    /**
     * 关联元器件
     */
    public function component()
    {
        return $this->hasOne('ComponentModel', 'serial_no', 'component_sn');
    }

    /**
     * 审核人
     */
    public function reviewer()
    {
        return $this->hasOne('UserModel', 'id', 'review_user_id');

    }

    /**
     * 提交人
     */
    public function user()
    {
        return $this->hasOne('UserModel', 'id', 'user_id');
    }

    /**
     * 关联的审核eda
     */
    public function edaReview()
    {
        return $this->hasOne('EdaReviewModel', 'component_sn', 'component_sn');
    }

    /**
     * 只显示已完成的记录
     * @param string $type
     * @param null   $key 物资编码或型号
     * @param int    $page_size
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getRecordsByType($type = self::TYPE_COMPONENT, $page_size = 12, $key = null)
    {
        // 判断是否有关键词搜索
        $component = null;
        if (!empty($key))
        {
            $model     = new ComponentModel();
            $component = $model->where('specification_model_no', $key)
                ->whereOr('serial_no', $key)
                ->field('serial_no')
                ->find();
        }

        // 查询数据
        $query = $this->with(['reviewer', 'user', 'component'])
            ->where('type', $type)
            ->where('status', 'complete')
            ->order('updated_at', 'desc');
        if (!empty($component))
        {
            $query = $query->where('component_sn', $component['serial_no']);
        }
        // 如果是eda，查询修改的原因
        if ($type == self::TYPE_EDA)
        {
            $query = $query->with(['edaReview']);
        }

        return $query->paginate($page_size);
    }

    /**
     * 获取用户的审核记录
     * @param string $type
     * @param int    $page_size
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public
    function getUserRecordsByType($type = self::TYPE_COMPONENT, $page_size = 12)
    {
        $user_id = session('user.id');
        $query   = $this->with('component')
            ->where('type', $type)
            ->where('user_id', $user_id)
            ->order('created_at', 'desc');
        return $query->paginate($page_size);
    }


    /**
     * 批量上传写入记录
     * @param $data
     * @return false|int
     */
    public function recordUpload($data)
    {
        $data['status']      = 'complete';
        $data['result']      = '管理员批量上传';
        $data['type']        = 'upload';
        return $this->save($data);
    }
}
