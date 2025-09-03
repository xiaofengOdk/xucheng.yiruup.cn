<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

use app\middleware\CsrfMiddleware;

// 在需要 CSRF 保护的路由上使用中间件
Route::post('/datareport/update/', ['app\controller\DataReportController', 'update'])->middleware([CsrfMiddleware::class]);