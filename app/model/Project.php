<?php

namespace app\model;

use plugin\admin\app\model\Admin;
use support\Model;
use support\Redis;

class Project extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'baidu_xinxiliu_project';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    function get_preject()
    {
        $project = Redis::get('project');
        if ($project == false) {
            $admin = Admin::get();
            $admin_map = [];
            foreach ($admin as $index => $item) {
                $admin_map[$item->id] = $item->username;
            }
            $project = $this->get();
            foreach ($project as $index => $item) {
                $project[$index]->sellName = $admin_map[$item['sellId']] ?? '';
                $project[$index]->youhuashiName = $admin_map[$item['youhuashiId']] ?? '';
            }
            $project_subName = [];
            foreach ($project as $index => $item) {
                $project_subName[$item->subName] = $item;
            }
            $project = json_encode($project);
            Redis::set('project', $project, 3600);
        }
        return json_decode($project);

    }

    function get_reject_index_subName()
    {
        $project = Redis::get('project_index_sub_name');
        if ($project == false) {
            $admin = Admin::get()->toArray();
            $admin_map = [];
            foreach ($admin as $index => $item) {
                $admin_map[$item['id']] = $item;
            }
            $project = $this->get();
            foreach ($project as $index => $item) {
                $project[$index]->sellName = $item['sellId'] > 0 && isset($admin_map[$item['sellId']]) ? $admin_map[$item['sellId']]['username'] : '';
                $project[$index]->youhuashiName = $item['youhuashiId'] > 0 && isset($admin_map[$item['youhuashiId']]) ? $admin_map[$item['youhuashiId']]['username'] : '';
                $project[$index]->weixin = $item['youhuashiId'] > 0 && $admin_map[$item['youhuashiId']] ? $admin_map[$item['youhuashiId']]['weixin'] : '';
            }
            $project_subName = [];
            foreach ($project as $index => $item) {
                $project_subName[$item->subName] = $item;
            }
            $project = json_encode($project_subName);
            Redis::set('project_index_sub_name', $project, 3600);
        }
        return json_decode($project, true);

    }

    function get_projectBysubName($subName, $adminId = 0)
    {
        $project = Redis::get('project_detail_by_subname' . $subName);

        if ($project == false) {
            $admin_map = Redis::get('admin_map');
            if (!$admin_map) {
                $admin = Admin::get()->toArray();
                $admin_map = [];
                foreach ($admin as $item) {
                    $admin_map[$item['id']] = $item;
                }
                $admin_map = json_encode($admin_map);
                Redis::set('admin_map', $admin_map, 3600 * 24);
                Redis::expire('admin_map', 3600*24);
            }
            $admin_map = json_decode($admin_map, true);
            $project = $this->where('subName', $subName)->get()->toArray();

            foreach ($project as $index => $item) {
                $item['youhuashiId'] == 0 && $item['youhuashiId'] = $adminId;
                $project[$index]['sellName'] = $item['sellId'] > 0 && isset($admin_map[$item['sellId']]) ? $admin_map[$item['sellId']]['username'] : '';
                $project[$index]['youhuashiName'] = $item['youhuashiId'] > 0 && isset($admin_map[$item['youhuashiId']]) ? $admin_map[$item['youhuashiId']]['username'] : '';
                $project[$index]['weixin'] = $item['youhuashiId'] > 0 && $admin_map[$item['youhuashiId']] ? $admin_map[$item['youhuashiId']]['weixin'] : '';
            }
            $project_subName = [];
            foreach ($project as $index => $item) {
                $project_subName[$item['subName']] = $item;
            }
            //如果 project 的优化师 id 为空 那就取 subuser 表关联的 adminId 数据
            if (empty($project) && $adminId > 0) {
                $project_subName[$subName]['sellName'] = '';
                $project_subName[$subName]['youhuashiName'] = isset($admin_map[$adminId]) ? $admin_map[$adminId]['username'] : '';
                $project_subName[$subName]['weixin'] = isset($admin_map[$adminId]) ? $admin_map[$adminId]['weixin'] : '';
            }
            $project = json_encode($project_subName);
            //缓存十分钟
            Redis::set('project_detail_by_subname' . $subName, $project, 600);
            Redis::expire('project_detail_by_subname' . $subName, 600);
        }

        return json_decode($project, true);

    }
}