<?php
declare(strict_types=1);

namespace Business\Hyperf\Middleware;

use Psr\Http\Message\ServerRequestInterface;

class CoreMiddleware extends \Hyperf\HttpServer\CoreMiddleware
{

    /**
     * Handle the response when cannot found any routes.
     */
    protected function handleNotFound(ServerRequestInterface $request): mixed
    {
        // 重写路由找不到的处理逻辑
        return $this->response()->withStatus(404);
    }

    /**
     * Handle the response when the routes found but doesn't match any available methods.
     */
    protected function handleMethodNotAllowed(array $methods, ServerRequestInterface $request): mixed
    {
        // 重写 HTTP 方法不允许的处理逻辑
        return $this->response()->withStatus(405);
    }
}
