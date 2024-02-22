<?php
declare(strict_types=1);

namespace Business\Hyperf\Middleware;

use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeader('origin') ?? '*';

        $response = Context::get(ResponseInterface::class);

        $response = $response->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true') //允许客户端发送cookie
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Headers', $request->getHeader('Access-Control-Request-Headers'))//设置 响应头 与 请求头 保持一致
            ->withHeader('Access-Control-Max-Age', 1728000) //该字段可选，用来指定本次预检请求的有效期，在此期间，不用发出另一条预检请求 20天。
            ->withHeader('Access-Control-Allow-Methods', implode(',', array_unique(['GET', 'POST', 'PUT', 'DELETE', strtoupper($request->getMethod()),
                    ])
                )
            ) //设置 允许的请求方法 与 请求头 保持一致
        ;
        Context::set(ResponseInterface::class, $response);

        if ($request->getMethod() == 'OPTIONS' || strpos($request->url(), "/ping") !== false) {
            return $response->withBody(new SwooleStream('success'));
        }

        return $handler->handle($request);
    }
}