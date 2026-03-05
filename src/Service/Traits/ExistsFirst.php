<?php

/**
 * base trait
 * User: Jmiy
 * Date: 2019-05-16
 * Time: 16:50
 */

namespace Business\Hyperf\Service\Traits;

use function Business\Hyperf\Utils\Collection\data_get;
use Business\Hyperf\Constants\Constant;

trait ExistsFirst
{
    /**
     * 检查是否存在
     * @param $where where条件
     * @param $getData 是否返回数据
     * @param $select 查询的字段
     * @param $orders 排序
     * @param string|array $connection 数据库连接
     * @param string|array|null $table 数据表
     * @return mixed
     */
    public static function existsOrFirst(
        $where = [],
        $getData = false,
        $select = null,
        $orders = [],
        string|array $connection = Constant::DB_CONNECTION_DEFAULT,
        string|array|null $table = null
    ): mixed
    {

        if (empty($where)) {
            return $getData ? [] : true;
        }

        $query = static::getModel($connection, $table)->buildWhere($where);

        if ($orders) {
            foreach ($orders as $order) {
                $query->orderBy(data_get($order, 0), data_get($order, 1));
            }
        }

        if ($getData) {
            if ($select !== null) {
                $query = $query->select($select);
            }
            $rs = $query->first();
        } else {
            $rs = $query->count();
        }

        return $rs;
    }

}
