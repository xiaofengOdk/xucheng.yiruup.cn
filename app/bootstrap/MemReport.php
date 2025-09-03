<?php
namespace app\bootstrap;

use Webman\Bootstrap;
use support\Log;
class MemReport implements Bootstrap
{
    public static function start($worker)
    {
        // 是否是命令行环境 ?
        $is_console = !$worker;
        if ($is_console) {
            // 如果你不想命令行环境执行这个初始化，则在这里直接返回
            return;
        }
        if($worker->id === 1) {
            // 每隔1小时执行一次
            \Workerman\Timer::add(3600, function () {
                $units = array('B', 'KB', 'MB', 'GB', 'TB');
                $bytes = max(memory_get_usage(), 0);
                $unitIndex = floor(($bytes ? log($bytes) : 0) / log(1024));
                Log::channel('task')->info("内存占用:" . sprintf('%.2f %s', $bytes / pow(1024, $unitIndex), $units[$unitIndex]) .'___时间：' . date("Y-m-d H:i:s", time()) . PHP_EOL);
            });
        }
    }

}