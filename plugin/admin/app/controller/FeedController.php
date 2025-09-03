<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\common\Auth;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Feed;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;


/**
 * 信息流计划定时删除
 */
class FeedController extends Crud
{
    /**
     * 以id为数据限制字段
     * @var string
     */
    protected $dataLimitField = 'id';


    public function __construct()
    {
        $this->model = new Feed;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {

        return raw_view('feed/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return raw_view('feed/insert',['uid'=>admin_id()]);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('feed/update',['uid'=>admin_id()]);
    }
    //暂停
    public function stop(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('user/update');
    }
    //删除
    public function delete(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::delete($request);
        }
        return raw_view('user/delete');
    }

}
