<?php

namespace Business\Hyperf\Middleware;

use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\HttpServer\Contract\RequestInterface;
use Business\Hyperf\Utils\PublicValidator;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidatorMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        /**
         * "Hyperf\HttpServer\Router\Dispatched" => array:3 [▼
         * //                "status" => 1
         * //                "handler" => array:3 [▼
         * //                    "callback" => array:2 [▼
         * //                        0 => "Business\Hyperf\Controller\DocController"
         * //                        1 => "encrypt"
         * //                    ]
         * //                    "route" => "/api/shop/encrypt[/{id:\d+}]"
         * //                    "options" => array:4 [▼
         * //                        "middleware" => []
         * //                        "as" => "test_user"
         * //                        "validator" => array:3 [▼
         * //                            "type" => "test"
         * //                            "messages" => []
         * //                            "rules" => []
         * //                        ]
         * //                        "nolog" => "test_nolog"
         * //                    ]
         * //                ]
         * //                "params" => array:1 [▼
         * //                    "id" => "996"
         * //                ]
         * //            ]
         */
        $routeInfo = $request->getAttribute(Dispatched::class);
        if (data_get($routeInfo, 'handler')) {
            $rules = [];
            $messages = [];
            $type = '';
            $validatorData = data_get($routeInfo, 'handler.options.validator', []);
            if ($validatorData) {
                $rules = data_get($validatorData, 'rules', []);
                $messages = data_get($validatorData, 'messages', []);
                $type = data_get($validatorData, 'type', $type);
            }

            $validator = PublicValidator::handle(getApplicationContainer()->get(RequestInterface::class)->all(), $rules, $messages, $type);
            if ($validator !== true) {//如果验证没有通过就提示用户
                return $validator;
            }
        }

        return $handler->handle($request);
    }

}
