<?php
//定时任务
namespace process;

use Workerman\Crontab\Crontab;

class Task
{
    public function onWorkerStart()
    {

        //信息流账户余额查询 每4分钟执行一次 更新baidu_xinxiliu_subuser表里的数据
        //new Crontab('0 */4 * * * *', function(){
        //     require app_path().'/crontab/XinxiliuSubuser.php';
        // });

        //信息流账户 展现 点击 消费 点击率	等查询 更新当天的 每五分钟更新一次，更新baidu_xinxiliu_subuser表里的数据
        //new Crontab('0 */5 * * * *', function(){
        //     require app_path().'/crontab/DataReport1.php';
        // });

        //信息流账户 展现 点击 消费 点击率	等查询 更新头30天的 每3小时更新一次，更新baidu_xinxiliu_subuser表里的数据
        // new Crontab('0 0 */3 * * *', function(){
        //     require app_path().'/crontab/DataReport2.php';
        // });

        //因为accessToken过期时间是1天，refreshToken过期时间是30天 所以当accessToken还有2小时过期的时候 用refreshToken刷新accessToken 每30分钟执行一次
        // new Crontab('0 */30 * * * *', function(){
        //     require app_path().'/crontab/RefreshToken.php';
        //  });
        // 项目表 更新昨日以及每月1日到昨天的表单 加粉 消耗 等 数据 用于运营每天生成报表 每天的凌晨1点18分执行 ，更新baidu_xinxiliu_project_reportdata表里的数据
        new Crontab('18 1 * * *', function () {
            //require app_path() . '/crontab/ProjectDataReport.php';
        });
        //优化师日报 企微推送
        new Crontab('30 17 * * *', function () {
            require app_path() . '/crontab/BaiduYhsPlan.php';
        });
        //推送两遍 保证成功
        new Crontab('32 17 * * *', function () {
            require app_path() . '/crontab/BaiduYhsPlan.php';
        });
    }

}