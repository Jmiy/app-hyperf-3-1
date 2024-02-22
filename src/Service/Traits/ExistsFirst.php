<?php

/**
 * base trait
 * User: Jmiy
 * Date: 2019-05-16
 * Time: 16:50
 */

namespace Business\Hyperf\Service\Traits;

use Business\Hyperf\Constants\Constant;

trait ExistsFirst
{

    /**
     * 检查是否存在
     * @param int $actId 活动id
     * @param int $customerId 会员id
     * @param array $where where条件
     * @param array $getData 是否获取记录  true:是  false:否
     * @param string|array $connection 商城id
     * @param string|array $table 国家
     * @return int|object
     */
    public static function existsOrFirst($where = [], $getData = false, $select = null, $orders = [], string|array $connection = Constant::DB_CONNECTION_DEFAULT, string|array $table = null)
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
