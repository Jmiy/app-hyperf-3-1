<?php

namespace Business\Hyperf\Utils\Redis\Lua;

use function Hyperf\Support\make;
class LuaFactory
{
    /**
     * 实现一个 __invoke() 方法来完成对象的生产，方法参数会自动注入一个当前的容器实例
     * @return LuaManager|mixed
     */
    public function __invoke()
    {
        return make(LuaManager::class);
    }
}
