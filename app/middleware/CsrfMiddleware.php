<?php
namespace app\middleware;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {
        // 检查是否为POST请求
        if ($request->method()=="POST") {
            $token = $request->session()->get('csrf_token');
            $submittedToken = $request->post('csrf_token', '');
            if (!$token || $token !== $submittedToken) {
                // Token 不匹配，返回错误
                return json(['error' => 'CSRF token mismatch.']);
            }
        }

        // 继续处理请求
        return $handler($request);
    }
}