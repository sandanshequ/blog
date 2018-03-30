<?php

namespace app\index\controller;

use app\index\model\UserModel;
use GuzzleHttp\Client;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\View;

class CommonController extends Controller
{

    // 定义角色类型
    const ROLE_ADMIN = 1;
    const ROLE_AUDIT = 2;
    const ROLE_EDA_REVIEW = 3;
    const ROLE_COMPONENT_REVIEW = 4;
    const ROLE_EDA_ADMIN = 5;
    const ROLE_COMPONENT_ADMIN = 6;
    const ROLE_NORMAL = 7;

    public $client;
    protected $user;

    public function initialize()
    {
        $this->client = new Client([
            'base_uri' => config('api_url'),
            'timeout'  => 20,
        ]);

        $user = session('user');
        $this->assign('nav_name', '');

        if (empty($user))
        {
            $this->redirect('index/login/login');
        } else
        {
            // 判断是否被删除
            $model       = new UserModel();
            $currentUser = $model->where('id', $user['id'])->find();
            if (empty($currentUser))
            {
                session(null);
                $this->error('该账号已被删除，无法进行该操作!', 'index/login/login', null, 2);
            }

            // 判断是否有被修改权限
            $relogin = Db::table('tbl_login_status')
                ->where('user_id', $user['id'])
                ->find();

            if ($relogin)
            {
                Db::table('tbl_login_status')
                    ->where('user_id', $user['id'])
                    ->delete();
                $this->error('您的权限被管理员修改，需要重新登录授权！', 'index/login/login', null, 2);
            }

            $this->user = $user;
            return redirect('index/');
        }

    }

    /**
     * 设置权限
     * @param $groupId
     */
    public function setAuthorize($groupId)
    {
        $group_ids = session('group_ids');

        if (!in_array($groupId, $group_ids))
        {
            $this->error('您没有权限进行该操作！');
        }
    }

    /**
     * 请求成功返回
     * @param       $data
     * @param array $header
     * @return \think\response\Json
     */
    public function ajaxSuccess($data, $header = [])
    {
        return json(['data' => $data, 'status' => 1], 200, $header);
    }

    /**
     * 请求失败返回
     * @param       $data
     * @param array $header
     * @return \think\response\Json
     */
    public function ajaxFail($data, $header = [])
    {
        return json(['data' => $data, 'status' => 0], 200, $header);
    }


    public
    function get($url = '', $params = null)
    {

        if (is_array($params))
        {
            $paramstr = "?";
            foreach ($params as $k => $v)
            {
                $paramstr .= "$k=$v&";
            }
            $paramstr = substr($paramstr, 0, strlen($paramstr) - 1);
            $result   = $this->client->request('GET', $url . $paramstr);
        } else
        {
            $result = $this->client->request('GET', $url . $params);

        }

        return $result;
    }

    public
    function post($url = '', $params = [])
    {
        $response = $this->client->post($url, ['form_params' => $params]);
        $result   = $response->getBody()->getContents();
        return $result;
    }

    public
    function delete($url = '', $params = null)
    {
        if (is_array($params))
        {
            $paramstr = "?";
            foreach ($params as $k => $v)
            {
                $paramstr .= "$k=$v&";
            }
            $paramstr = substr($paramstr, 0, strlen($paramstr) - 1);
            $result   = $this->client->request('GET', $url . $paramstr);
        } else
        {
            $result = $this->client->request('GET', $url . $params);

        }
        return $result;
    }

    public
    function put($url = '', $params = [])
    {
        $response = $this->client->put($url, ['form_params' => $params]);
        $result   = $response->getBody()->getContents();
        return $result;
    }


    public
    function addData($url, $params = array())
    {
        try
        {
            $result = $this->client->request('POST', $url, ['form_params' => $params]);
            $result = json_decode($result->getBody(), true);
        } catch (Exception $e)
        {
            $result = false;
        }

        return $result;
    }

    public
    function getData($url, $params = null)
    {
        try
        {
            if (is_array($params))
            {
                $paramstr = "?";
                foreach ($params as $k => $v)
                {
                    $paramstr .= "$k=$v&";
                }
                $paramstr = substr($paramstr, 0, strlen($paramstr) - 1);
                $result   = $this->client->request('GET', $url . $paramstr);
            } else
            {
                $result = $this->client->request('GET', $url . $params);

            }
            $result = json_decode($result->getBody(), true);
        } catch (Exception $e)
        {
            return false;
        }
        return $result;
    }

    public
    function updateData($url, $params = array())
    {
        if (isset($params['id']))
        {
            $id = $params['id'];
            unset($params['id']);
            $result = $this->client->request('PUT', $url . $id, ['form_params' => $params]);

        } else
        {
            $result = $this->client->request('PUT', $url, ['form_params' => $params]);

        }

        $result = json_decode($result->getBody(), true);
        return $result;
    }

    //TODO: 测试 各种角色 的访问权限
    public
    function check(int $role = self::ROLE_NORMAL)
    {
        $groups    = session('groups');
        $groups_id = array_column($groups, 'id');
        // 如果拥有权限，或为管理员
        if (in_array($role, $groups_id) || in_array(1, $groups_id))
        {
            return true;
        }
        $this->error("你没有权限进行该操作");
    }
}
