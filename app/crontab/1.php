<?php
//余额不足，企业微信通知。
namespace app\crontab;

use app\model\Project;
use support\Db;
use support\Log;
use Workerman\Http\Client;
use GuzzleHttp\Client as GuzzleHttp;
use GuzzleHttp\Promise\Utils;

$project = new Project;
$project = $project->get_projectBysubName('X-声必扬',6);
var_dump($project);
