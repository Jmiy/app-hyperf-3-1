<?php

declare(strict_types=1);
/**
 * This file is part of DTM-PHP.
 *
 * @license  https://github.com/dtm-php/dtm-client/blob/master/LICENSE
 */

namespace Business\Hyperf\Service\Distributed\Transaction\Dtm\Middleware;

use App\JsonRpc\Consumers\BaseConsumer;
use Business\Hyperf\Constants\Constant;
use DtmClient\Annotation\Barrier as BarrierAnnotation;
use DtmClient\Barrier;
use DtmClient\Constants\Protocol;
use DtmClient\Constants\Result;
use DtmClient\Exception\FailureException;
use DtmClient\Exception\OngingException;
use DtmClient\Exception\RuntimeException;
use DtmClient\TransContext;
use Hyperf\Collection\Arr;
use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Grpc\StatusCode;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Hyperf\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\go;

class DtmMiddleware implements MiddlewareInterface
{
    protected Barrier $barrier;

    protected ResponseInterface $response;

    protected ConfigInterface $config;

    public function __construct(Barrier $barrier, ResponseInterface $response, ConfigInterface $config)
    {
        $this->barrier = $barrier;
        $this->response = $response;
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
//        $queryParams = $request->getQueryParams() ?: $request->getParsedBody();
        $queryParams = Arr::collapse([$request->getParsedBody(), $request->getQueryParams()]);
        $headers = $request->getHeaders();
//        $transType = $headers['dtm-trans_type'][0] ?? $queryParams['trans_type'] ?? null;
//        $gid = $headers['dtm-gid'][0] ?? $queryParams['gid'] ?? null;
//        $branchId = $headers['dtm-branch_id'][0] ?? $queryParams['branch_id'] ?? null;
//        $op = $headers['dtm-op'][0] ?? $queryParams['op'] ?? null;
//        $phase2Url = $headers['dtm-phase2_url'][0] ?? $queryParams['phase2_url'] ?? null;
//        $dtm = $headers['dtm-dtm'][0] ?? null;

        $transType = $queryParams['trans_type'] ?? $headers['dtm-trans_type'][0] ?? null;
        $gid = $queryParams['gid'] ?? $headers['dtm-gid'][0] ?? null;
        $branchId = $queryParams['branch_id'] ?? $headers['dtm-branch_id'][0] ?? null;
        $op = $queryParams['op'] ?? $headers['dtm-op'][0] ?? null;
        $phase2Url = $queryParams['phase2_url'] ?? $headers['dtm-phase2_url'][0] ?? null;
        $dtm = $headers['dtm-dtm'][0] ?? null;

//        var_dump(
//            $request->getMethod(),
//            $request->getUri()->getPath(),
//            $request->getProtocolVersion(),
//            $headers,
//            $request->getQueryParams(),
//            $request->getParsedBody(),
//            $request->getAttribute(Dispatched::class)
//        );

        if ($transType && $gid && $branchId && $op) {
            $this->barrier->barrierFrom($transType, $gid, $branchId, $op, $phase2Url, $dtm);

            $requestBodyContents = data_get($queryParams, ['requestBodyContents']) ?? (data_get($headers, ['x-jmiy-request-body', 0]) ?? '');
            $header = [
                'dtm-gid' => $gid,
                'dtm-trans_type' => $transType,
                'dtm-branch_id' => $branchId,
                'dtm-op' => $op,
                'x-jmiy-request-body' => $requestBodyContents,
            ];

            //设置 协程上下文请求数据
            $contextRequestData = Arr::collapse([
                $queryParams,
                $header,
                [
                    'requestBodyContents' => $requestBodyContents ? json_decode($requestBodyContents, true) : $requestBodyContents,
                ]
            ]);
            Context::set(Constant::CONTEXT_REQUEST_DATA, $contextRequestData);
            BaseConsumer::setHeaders($header);

            if (isset($contextRequestData['customData'])) {
                unset($contextRequestData['requestBodyContents']);
                TransContext::setCustomData(json_encode($contextRequestData));
            }

        }

        /** @var Dispatched $dispatched */
        $dispatched = $request->getAttribute(Dispatched::class);
        if ($dispatched instanceof Dispatched && !empty($dispatched->handler->callback)) {
            $callback = $dispatched->handler->callback;

            if (is_callable($callback)) {
                // unsupported use barrier in callable
                return $handler->handle($request);
            }

            $router = $this->parserRouter($callback);
            $class = $router['class'];
            $method = $router['method'];

            $barrier = $this->config->get('dtm.barrier.apply', []);

            $businessCall = function () use ($handler, $request) {
                return $handler->handle($request);
            };

            if (in_array($class . '::' . $method, $barrier)) {
                return $this->handlerBarrierCall($businessCall);
            }

            $annotations = AnnotationCollector::getClassMethodAnnotation($class, $method);

            if (isset($annotations[BarrierAnnotation::class])) {
                return $this->handlerBarrierCall($businessCall);
            }
        }

        return $handler->handle($request);
    }

    protected function parserRouter(array|string $callback): array
    {
        if (is_array($callback)) {
            [$class, $method] = $callback;
        }

        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback);
        }

        if (is_string($callback) && str_contains($callback, '::')) {
            [$class, $method] = explode('::', $callback);
        }

        if (!isset($class) || !isset($method)) {
            throw new RuntimeException('router not exist');
        }

        return ['class' => $class, 'method' => $method];
    }

    protected function handlerBarrierCall(callable $businessCall): ResponseInterface
    {
        $response = $this->response;
        if ($this->isGRPC()) {
            $response = $response
                ->withBody(new SwooleStream(\DtmClient\Grpc\GrpcParser::serializeMessage(null)))
                ->withAddedHeader('Server', 'Hyperf')
                ->withAddedHeader('Content-Type', 'application/grpc')
                ->withAddedHeader('trailer', 'grpc-status, grpc-message');
        }

        try {
            $this->barrier->call($businessCall);
            $response = $response->withStatus(200);
            $this->isGRPC() && $response = $response->withTrailer('grpc-status', (string)StatusCode::OK)->withTrailer('grpc-message', 'ok');
            return $response;
        } catch (OngingException $ongingException) {
//            go(function () use ($businessCall, $ongingException) {
//                throw $ongingException;
//            });
            $code = $this->isGRPC() ? 200 : $ongingException->getCode();
            $response = $response->withStatus($code);
            $this->isGRPC() && $response = $response->withTrailer('grpc-status', (string)$ongingException->getCode())->withTrailer('grpc-message', $ongingException->getMessage());
            return $response;
        } catch (FailureException $failureException) {
//            go(function () use ($businessCall, $failureException) {
//                throw $failureException;
//            });
            $code = $this->isGRPC() ? 200 : $failureException->getCode();
            $response = $response->withStatus($code);
            $this->isGRPC() && $response = $response->withTrailer('grpc-status', (string)$failureException->getCode())->withTrailer('grpc-message', $failureException->getMessage());
            return $response;
        } catch (\Throwable $throwable) {

//            go(function () use ($businessCall, $throwable) {
//                throw $throwable;
//            });

            $code = $this->isGRPC() ? 200 : Result::FAILURE_STATUS;
            $response = $response->withStatus($code);
            $this->isGRPC() && $response = $response->withTrailer('grpc-status', (string)Result::FAILURE_STATUS)->withTrailer('grpc-message', $throwable->getMessage());
            return $response;
        }
    }

    protected function isGRPC(): bool
    {
        return $this->config->get('dtm.protocol') === Protocol::GRPC;
    }

}
