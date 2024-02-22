<?php

/**
 * base trait
 * User: Jmiy
 * Date: 2019-05-16
 * Time: 16:50
 */

namespace Business\Hyperf\Service\Traits;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Model\BaseModel;
use Hyperf\Database\Model\Relations\Relation;

trait GetDefaultConnectionModel
{
    /**
     * 获取模型 model
     * @param string|array|null $connection 数据库连接 强制使用model配置的connection
     * @param string|array|null $table 表名 默认使用model配置的表名
     * @param array|null $parameters model初始化参数
     * @param string|null $make model别名 默认:null
     * @param Relation|null $relation 关联对象
     * @param array|null $dbConfig 数据库配置
     * @return BaseModel|Relation|string|null
     */
    public static function getModel(string|array $connection = Constant::DB_CONNECTION_DEFAULT, string|array $table = null, ?array $parameters = [], ?string $make = null, ?Relation &$relation = null, ?array $dbConfig = [])
    {
        $baseConfig = static::handleDbConfig($connection, $table);
        $connection = data_get($baseConfig, Constant::CONNECTION);
        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        //data_set($parameters, 'attributes.storeId', $connection, false); //设置 model attributes.storeId
        return BaseModel::createModel(Constant::DB_EXECUTION_PLAN_DEFAULT_CONNECTION . $table, static::getMake($make), $parameters, $table, $relation, $dbConfig);
    }

}
