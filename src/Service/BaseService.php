<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Service;

use Hyperf\Collection\Arr;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\Traits\Base;
use Business\Hyperf\Service\Traits\ExistsFirst;
use Business\Hyperf\Service\Traits\HandleCache;
use Business\Hyperf\Service\Traits\BaseDb;
use Business\Hyperf\Service\Traits\Aspect;
use Business\Hyperf\Service\Traits\BaseClient;
use Business\Hyperf\Service\Traits\BaseFile;
use Business\Hyperf\Service\Traits\Queue;
use Business\Hyperf\Utils\Response;

class BaseService
{
    use Base,
        BaseDb,
        ExistsFirst,
        HandleCache,
        Aspect,
        BaseClient,
        BaseFile,
        Queue;

    /**
     * 获取公共参数
     * @param array $params 请求参数
     * @return array
     */
    public static function getPublicData($data, $order = [])
    {
        $page = $data['page'] ?? 1;
        $limit = $data[Constant::PAGE_SIZE] ?? 50;
        $offset = $limit * ($page - 1);
        $pagination = [
            'page_index' => $page,
            Constant::PAGE_SIZE => $limit,
            'offset' => $offset,
        ];

        $params[Constant::ORDER_BY] = $params[Constant::ORDER_BY] ?? '';
        if (
            $params[Constant::ORDER_BY] &&
            is_array($params[Constant::ORDER_BY]) &&
            count($params[Constant::ORDER_BY]) == 2 &&
            $params[Constant::ORDER_BY][0] &&
            $params[Constant::ORDER_BY][1] &&
            in_array($params[Constant::ORDER_BY][1], ['asc', 'desc'])
        ) {
            $order[0] = $params[Constant::ORDER_BY][0];
            $order[1] = $params[Constant::ORDER_BY][1];
        }

        return [
            Constant::DB_EXECUTION_PLAN_PAGINATION => $pagination,
            'where' => [],
            'order' => $order,
        ];
    }

    /**
     * 获取数据列表
     * @param array $data
     * @param boolean $toArray 是否转化为数组 true:是 false:否 默认:false
     * @param boolean $isPage 是否分页 true:是 false:否 默认:true
     * @param array $select
     * @param boolean $isRaw 是否原始 select
     * @param boolean $isGetQuery 是否获取 query
     * @return array|\Hyperf\Database\Model\Builder
     */
    public static function getList($data, $toArray = false, $isPage = true, $select = [], $isRaw = false, $isGetQuery = false)
    {

        $query = $data['query'];
        unset($data['query']);

        $data[Constant::DATA] = [];
        if (empty($query)) {

            if ($isGetQuery) {
                return $query;
            }

            unset($query);
            return $data;
        }

        if ($isRaw) {
            $query = $query->selectRaw(implode(',', $select));
        } else {
            $query = $query->select($select);
        }

        if ($isPage) {
            $offset = $data[Constant::DB_EXECUTION_PLAN_PAGINATION]['offset'];
            $limit = $data[Constant::DB_EXECUTION_PLAN_PAGINATION][Constant::PAGE_SIZE];
            $query = $query->offset($offset)->limit($limit);
        }

        if ($isGetQuery) {
            return $query;
        }

        $_data = $query->get();

        $_data = $_data ? ($toArray ? $_data->toArray() : $_data) : ($toArray ? [] : $_data);
        $data[Constant::DATA] = $_data;

        unset($query);

        return $data;
    }

    public static function getResponseData($requestFailedCode, $errorCode, $data)
    {

        if ($data === false || $data === null) {//如果请求接口失败，就直接返回推送失败
            return Response::getDefaultResponseData($requestFailedCode);
        }

        $errors = data_get($data, 'errors');
        if (empty($errors)) {
            return true;
        }

        $_msg = [];
        foreach ($errors as $key => $value) {
            $_msg[] = $key . ': ' . implode('|', Arr::flatten($value));
        }

        $errorsMsg = implode(', ', $_msg);
        return Response::getDefaultResponseData($errorCode, $errorsMsg);
    }
}
