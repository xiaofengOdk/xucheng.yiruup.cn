<?php
namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class Cors implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 允许来自任意源或指定来源
        $origin = $request->header('origin', '*.xuchengtop.com');
        $headers = [
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods'     => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers'     => $request->header('access-control-request-headers', '*'),
        ];

        // 如果是预检请求，提前返回空响应
        if ($request->method() === 'OPTIONS') {
            return response('', 204)->withHeaders($headers);
        }

        // 继续处理实际请求
        $response = $next($request);
        return $response->withHeaders($headers);
    }
}