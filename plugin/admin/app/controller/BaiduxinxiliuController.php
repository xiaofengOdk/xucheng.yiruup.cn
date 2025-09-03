<?php

namespace plugin\admin\app\controller;

use Illuminate\Support\Facades\DB;
use plugin\admin\app\common\Auth;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\model\Rule;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use plugin\admin\app\model\BaiduXinxiliuSubuser;
use plugin\admin\app\model\BaiduXinxiliuReportData;
use plugin\admin\app\model\BaiduxinxiliuProject;

class BaiduxinxiliuController extends Crud
{
    /**
     * 以id为数据限制字段
     * @var string
     */
    protected $dataLimitField = 'id';


    public function __construct()
    {
        $this->model = new BaiduXinxiliuSubuser;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {

        return raw_view('baiduxinxiliu/index');
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

        //余额
        $balance = $request->get('balance', '');
        if ($balance !== '') {
            $where[] = ['balance', '<', (int)$balance];
        }
        //账户名称
        $userName = $request->get('userName', '');
        if ($userName !== '') {
            $where[] =['userName','like','%'.$userName.'%'];
        }
        //账户id
        $userId = $request->get('userId', '');
        if ($userId !== '') {
            $where[] = ['userId', (int)$userId];
        }
        //status
        $status = $request->get('status', '');
        if ($status !== '') {
            $where[] = ['status', (int)$status];
        } else {
            //$where[] = ['status', 1];
        }
        // 百度账户状态userStat
        $userStat = $request->get('userStat', '');
        if ($userStat !== '') {
            $where[] = ['userStat', (int)$userStat];
        }
        $created_at = $request->get('created_at', '');
        $paginator = $this->model
            ->where($where)
            ->where(function ($query) use ($created_at, $where_youhuashi) {
                //是管理员帐号 查全部的优化师数据
                if (in_array(1, session('admin')['roles'])) {
                    if ($where_youhuashi !== '') {
                        $query->where('adminId', (int)$where_youhuashi);
                    }
                } else {//优化师只能查自己的数据和暂未关联的数据
                    if ($where_youhuashi !== '') {
                        $query->where('adminId', (int)$where_youhuashi);
                    } else
                        $query->whereIn('adminId', [0, admin_id()]);
                }
                if ($created_at !== '' && is_array($created_at) && $created_at[0] != false && $created_at[1] != false) {
                    $query->whereBetween('created_at', [$created_at[0], $created_at[1]]);
                }
            })
            ->orderBy($field, $order)
            ->paginate($limit);
        $items = $paginator->items();

        //查询 优化师分组的 id
        $admin_ids = AdminRole::where('role_id', 2)->pluck('admin_id')->toArray();
        //查询 优化师数据
        $youhuashi = Admin::whereIn('id', $admin_ids)->get()->toArray();
        $y = [];
        foreach ($youhuashi as $v) {
            $y[$v['id']] = $v['username'];
        }
        //账户状态
        $userStat = [1 => '开户金未到', 2 => '生效', 3 => '账户余额为0', 4 => '被拒绝', 6 => '审核中', 7 => '被禁用', 8 => '待激活', 11 => '账户预算不足'];

        //展 点 消 数据展示
        $userId = array_column($items, 'userId');
        $eventDate = $request->get('eventDate', '');
        if ($eventDate == '')
            $eventDate = date('Y-m-d', time());
        $reportData = BaiduXinxiliuReportData::whereIn('userId', $userId)->where('eventDate', $eventDate)->get();
        $reportData_map = [];
        foreach ($reportData as $report) {
            $reportData_map[$report['userId']] = $report;
        }
        //项目显示
        $userName_a = array_column($items, 'userName');
        $project = BaiduxinxiliuProject::whereIn('subName', $userName_a)->get();
        $project_map = [];
        foreach ($project as $p) {
            $project_map[$p['subName']] = $p;
        }
        foreach ($items as $index => $item) {
            $items[$index]['projectName'] = isset($project_map[$item['userName']]) ? $project_map[$item['userName']]['clientName'] : '暂未关联';
            if (($items[$index]['projectName']) != '暂未关联') {
                //表单
                if ($project_map[$item['userName']]['types'] == 1) {
                    $items[$index]['projectName'] = '<a href="/datareport/show/?project=' . mcrypt_encode($project_map[$item['userName']]['id']) . '&date=' . date("Y-m-d", strtotime("-1 day")) . '" target="_blank">' . $items[$index]['projectName'] . '</a>';;
                } else { //加粉
                    $items[$index]['projectName'] = '<a href="/datareport/list/?project=' . mcrypt_encode($project_map[$item['userName']]['id']) . '&date=' . date("Y-m-d", strtotime("-1 day")) . '" target="_blank">' . $items[$index]['projectName'] . '</a>';;
                }

            }
            $items[$index]['payOn'] = isset($project_map[$item['userName']]) ? $project_map[$item['userName']]['payOn'] : '';
            $items[$index]['youhuashi'] = isset($y[$item['adminId']]) ? $y[$item['adminId']] : '暂未关联';
            $items[$index]['userStatName'] = isset($userStat[$item['userStat']]) ? $userStat[$item['userStat']] : '未知';
            $items[$index]['impression'] = isset($reportData_map[$item['userId']]['impression']) ? $reportData_map[$item['userId']]['impression'] : 0;
            $items[$index]['click'] = isset($reportData_map[$item['userId']]['click']) ? $reportData_map[$item['userId']]['click'] : 0;
            $items[$index]['cost'] = isset($reportData_map[$item['userId']]['cost']) ? $reportData_map[$item['userId']]['cost'] : 0;
            $items[$index]['ctr'] = isset($reportData_map[$item['userId']]['ctr']) ? $reportData_map[$item['userId']]['ctr'] : 0;
            $items[$index]['cpc'] = isset($reportData_map[$item['userId']]['cpc']) ? $reportData_map[$item['userId']]['cpc'] : 0;
            $items[$index]['cpm'] = isset($reportData_map[$item['userId']]['cpm']) ? $reportData_map[$item['userId']]['cpm'] : 0;
            $items[$index]['eventDate'] = isset($reportData_map[$item['userId']]['eventDate']) ? $reportData_map[$item['userId']]['eventDate'] : $eventDate;

        }
        return json(['code' => 0, 'msg' => 'ok', 'count' => $paginator->total(), 'data' => $items]);
    }

    public function youhuashiselect(Request $request): Response
    {
        //查询 优化师分组的 id
        $admin_ids = AdminRole::where('role_id', 2)->pluck('admin_id')->toArray();
        //查询 优化师数据
        $youhuashi = Admin::whereIn('id', $admin_ids)->get()->toArray();
        $data[0]['value'] = 0;
        $data[0]['name'] = '暂未关联';
        foreach ($youhuashi as $k => $v) {
            $data[$k + 1]['name'] = $v['username'];
            $data[$k + 1]['value'] = $v['id'];
        }
        return $this->json(0, 'ok', $data);

    }

    public function sellselect(Request $request): Response
    {
        //查询 销售分组的 id
        $admin_ids = AdminRole::where('role_id', 3)->pluck('admin_id')->toArray();
        //查询 优化师数据
        $youhuashi = Admin::whereIn('id', $admin_ids)->get()->toArray();
        $data[0]['value'] = 0;
        $data[0]['name'] = '暂未关联';
        foreach ($youhuashi as $k => $v) {
            $data[$k + 1]['name'] = $v['username'];
            $data[$k + 1]['value'] = $v['id'];
        }
        return $this->json(0, 'ok', $data);

    }

    public function selectById(Request $request): Response
    {
        $id = $request->get('id', 0);
        $data = $this->model->where('id', $id)->get()->toArray();
        return $this->json(0, 'ok', $data);

    }

    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $youhuashiId = $request->post('youhuashi', 0);
            $status = $request->post('status');
            $id = $request->post('id');
            if (!$id) {
                return $this->json(1, '缺少参数');
            }
            if ($youhuashiId >= 0)
                $this->model->where('id', $id)->update(['adminId' => (int)$youhuashiId]);

            if ($status) {
                $this->model->where('id', $id)->update(['status' => (int)$status]);
                return $this->json(0);
            }
        }
        return raw_view('baiduxinxiliu/update');
    }

}