<?php

namespace plugin\admin\app\controller;
use plugin\admin\app\common\Auth;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\model\BaiduXinxiliuReportData;
use plugin\admin\app\model\BaiduXinxiliuSubuser;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use plugin\admin\app\model\BaiduxinxiliuProject;
class BaiduxinxiliuprojectController extends Crud
{
    /**
     * 以id为数据限制字段
     * @var string
     */
    protected $dataLimitField = 'id';


    public function __construct()
    {
        $this->model = new BaiduxinxiliuProject;
    }
    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index() : Response
    {

        return raw_view('baiduxinxiliuproject/index');
    }
    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        $order = $request->get('order', 'desc');
        $format = $request->get('format', 'normal');
        $field = $request->get('field', 'id');
        $limit = (int)$request->get('limit', $format === 'tree' ? 1000 : 20);
        $limit = $limit <= 0 ? 20 : $limit;
        $order = $order === 'asc' ? 'asc' : 'desc';

        $page = (int)$request->get('page');
        $page = $page > 0 ? $page : 1;
        //优化师id
        $where_youhuashi = $request->get('youhuashi', '');
        $where = [];
        if ($where_youhuashi !== '') {
            $where[] = ['adminId', (int)$where_youhuashi];
        }
        //端口
        $portName = $request->get('portName','');
        if($portName!==''){
            $where[] =['portName',$portName];
        }
        //主账户名称
        $masterName = $request->get('masterName','');
        if($masterName!==''){
            $where[] =['masterName',$masterName];
        }
        //客户名称
        $clientName = $request->get('clientName','');
        if($clientName!==''){
            $where[] =['clientName','like','%'.$clientName.'%'];
        }
        //子账户名称
        $subName = $request->get('subName','');
        if($subName!==''){
            $where[] =['subName','like','%'.$subName.'%'];
        }
        $paginator = $this->model
            ->where($where)
            ->where(function ($query)  {
                //销售只能查看自己的
                if (in_array(3, session('admin')['roles'])&&
                    !in_array(1, session('admin')['roles'])) {
                        $query->where('sellId', admin_id());
                }
            })
            ->orderBy($field, $order)
            ->paginate($limit);
        $items = $paginator->items();

        //查询 优化师分组的 id
        $admin_ids = AdminRole::where('role_id', 2)->pluck('admin_id')->toArray();
        //查询 优化师数据
        $youhuashi=Admin::whereIn('id',$admin_ids)->get()->toArray();
        $y=[];
        foreach($youhuashi as $v){
            $y[$v['id']]=$v['username'];
        }
        //查询 销售分组的 id
        $admin_ids = AdminRole::where('role_id', 3)->pluck('admin_id')->toArray();
        //查询 销售数据
        $sell=Admin::whereIn('id',$admin_ids)->get()->toArray();
        $s=[];
        foreach($sell as $v){
            $s[$v['id']]=$v['username'];
        }
        $userName_a = array_column($items, 'subName');
        $subUser = BaiduXinxiliuSubuser::whereIn('userName', $userName_a)->get()->toArray();
        $subUser_map = [];
        foreach ($subUser as $ss) {
            $subUser_map[$ss['userName']] = $ss;
        }
        //展 点 消 数据展示
        $userId = array_column($subUser, 'userId');

        $eventDate=date('Y-m-d',time());
        $reportData = BaiduXinxiliuReportData::whereIn('userId', $userId)->where('eventDate',$eventDate)->get()->toArray();
        $reportData_map = [];
        foreach ($reportData as $report) {
            $reportData_map[$report['userId']] = $report;
        }
        foreach ($items as $index => $item) {
            $items[$index]['youhuashi'] = isset($y[$item['youhuashiId']] )?$y[$item['youhuashiId']] :'暂未关联';
            $items[$index]['sell'] = isset($s[$item['sellId']] )?$s[$item['sellId']] :'暂未关联';
            $items[$index]['mid'] = mcrypt_encode($item['id']);
            $items[$index]['balance'] = isset($subUser_map[$item['subName']] )?$subUser_map[$item['subName']]['balance'] :0;
            $items[$index]['cost']=isset($subUser_map[$item['subName']]['userId'])&&isset($reportData_map[$subUser_map[$item['subName']]['userId']]['cost'])?$reportData_map[$subUser_map[$item['subName']]['userId']]['cost']:0;
        }
        return json(['code' => 0, 'msg' => 'ok', 'count' => $paginator->total(), 'data' => $items]);
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
        return raw_view('baiduxinxiliuproject/add');
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
        return raw_view('baiduxinxiliuproject/update');
    }
    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $id = (int)$request->get('id');
        var_dump($id);
        $this->model->where('id',$id)->delete();
        return $this->json(0);
    }
    public  function selectById(Request $request): Response
    {
        $id = $request->get('id', 0);
        $data = $this->model->where('id', $id)->get()->toArray();
        return $this->json(0, 'ok', $data);

    }
}