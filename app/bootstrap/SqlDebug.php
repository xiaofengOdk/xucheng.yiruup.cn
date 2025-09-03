<?php
namespace app\bootstrap;

use Illuminate\Database\Events\QueryExecuted;
use support\Db;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Webman\Bootstrap;
use support\Log;
class SqlDebug implements Bootstrap
{
    /**
     * 自定义输出格式，否则输出前面会带有当前文件，无用信息
     * @param $var
     * @return void
     */
    public static function dumpvar($var): void
    {
        try {
            $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
            $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();

            // 如果 STDOUT 不可写，可重定向到文件
            if (!is_resource(STDOUT) || @fwrite(STDOUT, '') === false) {
                // 改用日志文件输出
                file_put_contents('/tmp/sql_debug.log', print_r($var, true) . PHP_EOL, FILE_APPEND);
            } else {
                $dumper->dump($cloner->cloneVar($var));
            }
        } catch (\Throwable $e) {
            // 可选日志记录错误信息
            file_put_contents('/tmp/sql_debug_error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }

    public static function start($worker)
    {
        if (!config("app.debug") || config("app.debug") === 'false') return;
        $appPath = app_path();
        Db::connection()->listen(function (QueryExecuted $queryExecuted) use ($appPath) {
            if (isset($queryExecuted->sql) and $queryExecuted->sql !== "select 1") {
                $bindings = $queryExecuted->bindings;
                $sql = array_reduce(
                    $bindings,
                    function ($sql, $binding) {
                        return preg_replace('/\?/', is_numeric($binding) ? $binding : "'" . $binding . "'", $sql, 1);
                    },
                    $queryExecuted->sql
                );
                // 这句话是打印所有的sql
               // self::dumpvar("[sql] [time:{$queryExecuted->time} ms] [{$sql}]");
                // 下面是只打印app目录下产生的sql语句
                $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                foreach ($traces as $trace) {
                    if (isset($trace['file']) && isset($trace["function"])) {
                        //只打印app下的sql并且不打印计划任务的sql
                        if (str_contains($trace['file'], 'app')&&!str_contains($trace['file'], 'crontab')&&!str_contains($trace['file'], 'Crontab1')&&!str_contains($trace['file'], 'model/Project')&&!str_contains($trace['file'], '/app/middleware/Cors')&&!str_contains($trace['file'], 'AdController')) {
                            $file = str_replace(base_path(), '', $trace['file']);
                            $str = "[file] {$file}:{$trace['line']} [function]:{$trace["function"]}";
                            self::dumpvar("[sql] [time:{$queryExecuted->time} ms] [{$sql}]");
                            self::dumpvar($str);
                            Log::channel('sql')->info("[sql] [time:{$queryExecuted->time} ms] [{$sql}]");

                        }
                    }
                }
            }
        });
    }
}