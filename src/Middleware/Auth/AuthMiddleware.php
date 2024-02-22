<?php
declare(strict_types=1);

namespace Business\Hyperf\Middleware\Auth;

use Hyperf\Context\ApplicationContext;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\MenuService;
use Business\Hyperf\Utils\Response;
use Hyperf\Cache\CacheManager;
use Hyperf\Contract\SessionInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Router\Dispatched;

/**
 * 获取 OAuth 2.0 鉴权后的用户数据，包含
 * admin_id (erp_base.b_user_admin.id)
 * user_id (erp_base.b_user.id 既 erp_base.b_user_admin.user_id)
 * is_master (erp_base.b_user_admin.is_master)
 * dbhost (erp_base.b_user.dbhost)
 * codeno (erp_base.b_user.codeno)
 *
 * 将保存在 ServerRequestInterface attribute 中，可通过
 * $request->getAttribute('userInfo') 获取，获取到的是数组数据，数组结构如下
 * [
 *     Constant::DB_COLUMN_ADMIN_ID => int,
 *     Constant::DB_COLUMN_USER_ID => int,
 *     Constant::DB_COLUMN_IS_MASTER => bool,
 *     Constant::DB_COLUMN_DBHOST => string,
 *     Constant::DB_COLUMN_CODENO => string,
 * ]
 *
 * 对于本地开发，未接入 OAuth 2.0 服务的情况下，可在 .env 中添加
 * MOCK_OAUTH2_USERINFO
 * 配置信息，格式为
 * sprintf(
 *     '%d:%d:%d:%s:%s',
 *     $adminId,
 *     $userId,
 *     $isMaster,
 *     $dbhost,
 *     $codeno
 * );
 * 如：MOCK_OAUTH2_USERINFO=304:229:1:001:001
 */
class AuthMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userDataKey = [];
        $userData = [];
        $authenticatedUserId = $request->getHeaderLine('x-authenticated-userid');
        if ('' !== $authenticatedUserId) {//如果是通过kong认证，就将 token 的数据设置到用户信息上下文中

            if (1 !== preg_match('/^\d+:\d+:[01]:\d{3}:\d{3}$/', $authenticatedUserId)) {
                //return Context::get(ResponseInterface::class)->withStatus(401, 'Unauthorized');
                return Response::json(['jump_url' => config('app.login')], 401);
            }

            $userData = explode(':', $authenticatedUserId);
            $userDataKey = [
                Constant::DB_COLUMN_ADMIN_ID => 0,//账号id
                Constant::DB_COLUMN_USER_ID => 1,//主账号id
                Constant::DB_COLUMN_IS_MASTER => 2,//是否主账号
                Constant::DB_COLUMN_DBHOST => 3,//数据库
                Constant::DB_COLUMN_CODENO => 4,//编号
            ];
        } else {
            $cookieParams = $request->getCookieParams();
            $sessionId = data_get($cookieParams, config('session.options.session_name', 'PHPSESSID'));
            if ($sessionId !== null) {//如果使用 session，就将 session 的数据设置到用户信息上下文中
                //$userData = ApplicationContext::getContainer()->get(SessionInterface::class)->all();
//                $session = ApplicationContext::getContainer()->get(RedisFactory::class)->get('session');
//                $userData = $session->get(config("session.options.prefix") . $sessionId);
//                $userData = $userData ? unserialize($userData) : [];
                $userData = ApplicationContext::getContainer()->get(CacheManager::class)->getDriver('session')->get($sessionId);
                $sessionPrefix = config("session.options.key_prefix");
                if (data_get($userData, $sessionPrefix . Constant::DB_COLUMN_ADMIN_ID) !== null) {//如果是用户控制面板
                    $userDataKey = [
                        Constant::DB_COLUMN_ADMIN_ID => $sessionPrefix . Constant::DB_COLUMN_ADMIN_ID,//账号id
                        Constant::DB_COLUMN_USER_ID => $sessionPrefix . Constant::DB_COLUMN_USER_ID,//主账号id
                        Constant::DB_COLUMN_IS_MASTER => $sessionPrefix . Constant::DB_COLUMN_IS_MASTER,//是否主账号
                        Constant::DB_COLUMN_DBHOST => $sessionPrefix . Constant::DB_COLUMN_DBHOST,//数据库
                        Constant::DB_COLUMN_CODENO => $sessionPrefix . Constant::DB_COLUMN_CODENO,//编号
                    ];
                } else {//如果是总台登录  data_get($userData, 'ship_sys_admin_id') !== null
                    $sessionPrefix = 'ship_';
                    $userDataKey = [
                        'username' => $sessionPrefix . 'username',//账号
                        Constant::DB_COLUMN_ADMIN_ID => $sessionPrefix . 'sys_admin_id',//账号id
                        Constant::DB_COLUMN_USER_ID => $sessionPrefix . 'sys_user_id',//主账号id
                        'role_id' => $sessionPrefix . 'sys_role_id',//角色id
                        'is_service' => $sessionPrefix . 'is_service',//是否服务
                        'jdxhash' => $sessionPrefix . 'jdxhash',//jdxhash
                        'email' => $sessionPrefix . 'email',//邮箱
                        'realname' => $sessionPrefix . 'realname',//账号名称
                    ];
                }
            }
        }

        //将账号数据 设置到 请求上下文
        if ($userData) {
            $userInfo = [];
            foreach ($userDataKey as $key => $value) {
                Context::set(Constant::CONTEXT_USRE_INFO . Constant::LINKER . $key, data_get($userData, $value));
                $userInfo[$key] = data_get($userData, $value);
            }
            $request = $request->withAttribute('userInfo', $userInfo);
            Context::set(ServerRequestInterface::class, $request);
        }

        //判断是否认证
        $userId = Context::get(Constant::CONTEXT_USRE_INFO . Constant::LINKER . Constant::DB_COLUMN_ADMIN_ID);
        if (empty($userId)) {
            return Response::json(['jump_url' => config('app.login')], 401);
        }

        //判断是否有权限
        /**
         * "Hyperf\HttpServer\Router\Dispatched" => array:3 [▼
         * //                "status" => 1
         * //                "handler" => (Hyperf\HttpServer\Router\Handler)array:3 [▼
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
//        $routeInfo = $request->getAttribute(Dispatched::class);
//        $rule = data_get($routeInfo, 'handler.route', '');
//        $method = $request->getMethod();
//
//        $permissionItem = MenuService::getResourceItem(config('app_type'), implode(':', [$method, $rule]));
//        $isHasPermission = data_get(MenuService::isHasPermission($userId, $permissionItem), Constant::DATA . Constant::LINKER . 'isHasPermission');
//        if (!$isHasPermission) {//如果没有权限,就直接提示没有权限
//            return Response::json([], 403);
//        }

        return $handler->handle($request);
    }
}
