<?php

/**
 * Db trait
 * User: Jmiy
 * Date: 2020-10-21
 * Time: 14:30
 */

namespace Business\Hyperf\Service\Traits;

use function Hyperf\Collection\data_set;
use function Hyperf\Support\call;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
use Hyperf\Collection\Arr;
use Hyperf\Coroutine\Coroutine;
use Throwable;
use function Hyperf\Coroutine\go;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Model\BaseModel;
use Hyperf\Database\ConnectionInterface;
use Hyperf\DbConnection\Db as DB;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Relations\Relation;
use function Hyperf\Support\env;

trait BaseDb
{

    /**
     * 获取模型别名
     * @return string
     */
    public static function getModelAlias()
    {
        return static::getCustomClassName();
    }

    /**
     * 获取 make
     * @param null|string $make
     * @return string
     */
    public static function getMake($make = null)
    {
        return $make === null ? static::getModelAlias() : $make;
    }

    /**
     * 创建model
     * @param string|null $connection 数据库连接 默认：default
     * @param string|null $make 模型别名
     * @param array|null $parameters 参数
     * @param string|null $table 表名 默认使用model配置的表名
     * @param Relation|null $relation 关联对象
     * @param array|null $dbConfig 数据库配置
     * @return BaseModel|Relation|string|null
     */
    public static function createModel(?string $connection = Constant::DB_CONNECTION_DEFAULT, ?string $make = null, ?array $parameters = [], ?string $table = null, ?Relation &$relation = null, ?array $dbConfig = [])
    {
        return BaseModel::createModel($connection, $make, $parameters, $table, $relation, $dbConfig);
    }


    /**
     * 获取模型 model
     * @param string|array|null $connection 数据库连接 默认：default
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

        $dbConfig = [
            'database' => $connection,
        ];

        return static::createModel($connection, static::getMake($make), $parameters, $table, $relation, $dbConfig);
    }

    /**
     * 添加
     * @param array $data 数据
     * @param bool|null $isGetId 是否返回 id true:是 false:否
     * @param string|array|null $connection 数据库连接 默认：default
     * @param string|array|null $table 表名 默认使用model配置的表名
     * @return bool|int
     * @throws \Throwable
     */
    public static function insert(array $data, bool $isGetId = false, string|array $connection = Constant::DB_CONNECTION_DEFAULT, string|array $table = null)
    {
        if (empty($data)) {
            return false;
        }

//        $retry = 0;
//        beginning:
//        try {
        if ($isGetId) {
            return static::getModel($connection, $table)->insertGetId($data);
        }

        return static::getModel($connection, $table)->insert($data);
//        } catch (\Throwable $throwable) {
//
//            if ($retry < 10) {
//                $retry = $retry + 1;
//                Coroutine::sleep(rand(2, 5));
//                goto beginning;
//            }
//
//            throw $throwable;
//        }
    }

    /**
     * 更新
     * @param array $where 更新的条件
     * @param array $data 更新的数据
     * @param array|null $handleData 执行更新时要附加的操作
     * @param string|array|null $connection 数据库连接 默认：default
     * @param string|array|null $table 表名 默认使用model配置的表名
     * @return int|bool
     * @throws \Throwable
     */
    public static function update(array $where, array $data, array $handleData = [], string|array $connection = Constant::DB_CONNECTION_DEFAULT, string|array $table = null): int|bool
    {
        if (empty($where) || empty($data)) {
            return false;
        }

//        $retry = 0;
//        beginning:
//        try {
        $model = static::getModel($connection, $table);

        $model = $model->buildWhere($where);
        if (is_array($handleData) && !empty($handleData)) {
            foreach ($handleData as $callback) {
                $model = call($callback, [$model]);
            }
        }

        return $model->update($data);
//        } catch (\Throwable $throwable) {
//
//            if ($retry < 10) {
//                $retry = $retry + 1;
//                Coroutine::sleep(rand(2, 5));
//                goto beginning;
//            }
//
//            throw $throwable;
//        }
    }

    /**
     * 删除
     * @param array $where 删除条件
     * @param array|null $handleData 执行数据库操作前必须通过的校验
     * @param string|array|null $connection 数据库连接 默认：default
     * @param string|array|null $table 表名 默认使用model配置的表名
     * @return int|bool
     * @throws \Throwable
     */
    public static function delete(array $where, array $handleData = [], string|array $connection = Constant::DB_CONNECTION_DEFAULT, string|array $table = null): int|bool
    {
        if (empty($where)) {
            return false;
        }

//        $retry = 0;
//        beginning:
//        try {
        $model = static::getModel($connection, $table);
        $model = $model->buildWhere($where);
        if (is_array($handleData) && !empty($handleData)) {
            foreach ($handleData as $callback) {
                $model = call($callback, [$model]);
            }
        }

        return $model->delete(); //逻辑删除
//        } catch (\Throwable $throwable) {
//
//            if ($retry < 10) {
//                $retry = $retry + 1;
//                Coroutine::sleep(rand(2, 5));
//                goto beginning;
//            }
//
//            throw $throwable;
//        }

    }

    /**
     * 更新或者新增记录
     * @param array $where where条件
     * @param array $data 数据
     * @param array|null $handleData 执行数据库操作前必须通过的校验
     * @param string|array|null $connection 数据库连接 默认：default
     * @param string|array|null $table 表名 默认使用model配置的表名
     * @return array [
     *        'lock' => $lock,
     *        'dbOperation' => data_get($rs, 'dbOperation', 'no'),
     *        'data' => $rs,
     *    ];
     * @throws \Throwable
     */
    public static function updateOrCreate(array $where, array $data, array $handleData = [], string|array $connection = Constant::DB_CONNECTION_DEFAULT, string|array $table = null)
    {
        $model = static::getModel($connection, $table);
        $key = serialize(Arr::collapse([
            [
                $model->getConnectionName(),
                $model->getTable(),
            ], $where
        ]));
        $key = md5($key);
        unset($model);

//        $select = data_get($handleData, Constant::DB_OPERATION_SELECT, []);
//        if (!empty($select)) {
//            $select = array_unique(Arr::collapse([[$model->getKeyName()], $select]));
//            $model = $model->select($select);
//        }

        $service = static::getNamespaceClass();

        $parameters = [
            function () use ($where, $data, $handleData, $connection, $table) {

//                $retry = 0;
//                beginning:

                $model = static::getModel($connection, $table);

                $select = data_get($handleData, Constant::DB_OPERATION_SELECT, []);
                if (!empty($select)) {
                    $select = array_unique(Arr::collapse([[$model->getKeyName()], $select]));
                    $model = $model->select($select);
                }
                data_set($where, 'handleData', $handleData);

//                try {
                return $model->updateOrCreate($where, $data); // ->select($select) updateOrCreate：不可以添加主键id的值  updateOrInsert：可以添加主键id的值
//                } catch (\Throwable $throwable) {
//
//                    if (false !== strpos($throwable->getMessage(), "wait_timeout")) {//如果不是主键冲突，就重试
//                        if ($retry < 3) {
//                            $retry = $retry + 1;
//                            Coroutine::sleep(rand(2, 5));
//                            goto beginning;
//                        }
//                    }
//
//                    throw $throwable;
//                }
            }
        ];
        $rs = $lock = static::handleLock([$key], $parameters);

        if ($rs === false) {//如果获取分布式锁失败，就直接查询数据
            $serialHandle = data_get($handleData, Constant::SERIAL_HANDLE, []);

            $forceRelease = data_get($serialHandle, 'forceRelease', true); //是否强制释放锁 true：是  false：否
            $releaseTime = data_get($serialHandle, 'releaseTime', 1);

            if ($forceRelease) {
                Coroutine::sleep($releaseTime);
                //释放锁
                $serialHandle = Arr::collapse(
                    [
                        $serialHandle,
                        [
                            getJobData($service, 'forceReleaseLock', [$key, 'forceRelease', 0]), //获取分布式锁失败时，强制释放锁
                        ]
                    ]
                );
            }
//            else {
//                $rs = $model->buildWhere($where)->first();
//            }

            foreach ($serialHandle as $handle) {
                $service = data_get($handle, Constant::SERVICE, '');
                $method = data_get($handle, Constant::METHOD, '');
                $parameters = data_get($handle, Constant::PARAMETERS, []);

                if (empty($service) || empty($method) || !method_exists($service, $method)) {
                    continue;
                }

                $service::{$method}(...$parameters);
            }

            return static::updateOrCreate(...func_get_args());
        }

//        $retry = 0;
//        beginning:
//
//        $model = static::getModel($connection, $table);
//
//        $select = data_get($handleData, Constant::DB_OPERATION_SELECT, []);
//        if (!empty($select)) {
//            $select = array_unique(Arr::collapse([[$model->getKeyName()], $select]));
//            $model = $model->select($select);
//        }
//        data_set($where, 'handleData', $handleData);
//
//        try {
//            $rs = $lock = $model->updateOrCreate($where, $data); // ->select($select) updateOrCreate：不可以添加主键id的值  updateOrInsert：可以添加主键id的值
//        } catch (\Throwable $throwable) {
//
//            if ($retry < 10) {
//                $retry = $retry + 1;
//                Coroutine::sleep(rand(2, 5));
//                goto beginning;
//            }
//
//            throw $throwable;
//        }

        return [
            'lock' => $lock,
            Constant::DB_OPERATION => $lock === false ? Constant::DB_OPERATION_SELECT : data_get($rs, Constant::DB_OPERATION, Constant::DB_OPERATION_DEFAULT),
            Constant::DATA => $rs,
        ];
    }

    /**
     * 获取数据库配置
     * @param array|null $parameters model初始化参数
     * @param string|null $make model别名 默认:null
     * @param string|null $table 表名 默认使用model配置的表名
     * @param string $connection 数据库连接 默认：default
     * @return mixed
     */
    public static function getDbConfig(?array $parameters = [], ?string $make = null, ?string $table = null, $connection = Constant::DB_CONNECTION_DEFAULT)
    {
        $model = static::getModel($connection, $table, $parameters, $make);
        $dbConfig = config(Constant::DATABASES . Constant::LINKER . $model->getConnectionName(), config(Constant::DATABASES . Constant::LINKER . Constant::DB_CONNECTION_DEFAULT));

        $tableName = $model->getTable();
        $prefix = data_get($dbConfig, 'prefix', ''); //表前缀
        $fullTable = $prefix . $tableName;
        $fullDbTable = '`' . implode('`.`', [data_get($dbConfig, 'database', ''), $fullTable]) . '`';
        data_set($dbConfig, 'table', $tableName, false);
        data_set($dbConfig, 'full_table', $fullTable, false);
        data_set($dbConfig, 'full_db_table', $fullDbTable, false);

        $tableAlias = $fullDbTable;
        $fullTableAlias = $fullDbTable;
        if ($model::TABLE_ALIAS !== null) {
            $tableAlias = $model::TABLE_ALIAS;
            $fullTableAlias = $prefix . $tableAlias;
        }
        data_set($dbConfig, 'table_alias', $tableAlias, false);
        data_set($dbConfig, 'full_table_alias', $fullTableAlias, false);

        if ($model::TABLE_ALIAS !== null) {
            data_set($dbConfig, 'raw_from', $fullDbTable . ' as ' . $fullTableAlias, false);
            data_set($dbConfig, 'from', $tableName . ' as ' . $tableAlias, false);
        } else {
            data_set($dbConfig, 'raw_from', $fullDbTable, false);
            data_set($dbConfig, 'from', $tableName, false);
        }


        data_set($dbConfig, 'username', null);
        data_set($dbConfig, 'password', null);
        return $dbConfig;
    }

    /**
     * 构建自定义属性 whereExists
     * @param array $whereFields where 数据
     * @param array $whereColumns 关联字段
     * @param array $extData 扩展数据
     * @param string $connection 数据库连接 默认：default
     * @return array
     */
    public static function buildWhereExists($whereFields, $whereColumns, $extData = [], $connection = Constant::DB_CONNECTION_DEFAULT)
    {

        $customizeWhere = [
            Constant::METHOD => 'whereExists',
            Constant::PARAMETERS => function ($query) use ($connection, $whereFields, $whereColumns, $extData) {


                $dbConfig = static::getDbConfig([], null, '', $connection);

                $query = $query->select(DB::raw(1))
                    ->from(DB::raw(data_get($dbConfig, 'raw_from', '')));

                $tableAlias = data_get($dbConfig, 'table_alias', '');
                foreach ($whereColumns as $whereColumn) {
                    $foreignKey = data_get($whereColumn, 'foreignKey');
                    $localKey = data_get($whereColumn, 'localKey');
                    $query = $query->whereColumn($tableAlias . Constant::LINKER . $foreignKey, $localKey);
                }


                foreach ($whereFields as $whereField) {

                    $keyWhere = Constant::DB_EXECUTION_PLAN_WHERE;
                    $_parameters = Constant::PARAMETER_ARRAY_DEFAULT;

                    $field = data_get($whereField, 'field');
                    $values = data_get($whereField, Constant::DB_COLUMN_VALUE);

                    $method = data_get($whereField, Constant::METHOD);
                    if ($method !== null) {
                        $keyWhere = $method;
                        $_parameters = data_get($whereField, Constant::PARAMETERS);
                    } else {
                        if (!empty($field)) {
                            if (is_array($values)) {
                                $values = array_unique($values);
                                $_parameters = [function ($query) use ($field, $values) {
                                    foreach ($values as $item) {
                                        $query->OrWhere($field, '=', $item);
                                    }
                                }];
                            } else {
                                $_parameters = [$field, 'like', '%' . $values . '%'];
                            }
                        }
                    }

                    $query = $query->{$keyWhere}(...$_parameters);
                }

                $joinData = data_get($extData, Constant::DB_EXECUTION_PLAN_JOIN_DATA, '');
                if ($joinData) {
                    foreach ($joinData as $joinItem) {
                        $table = data_get($joinItem, Constant::DB_EXECUTION_PLAN_TABLE, '');
                        $first = data_get($joinItem, Constant::DB_EXECUTION_PLAN_FIRST, '');
                        $operator = data_get($joinItem, Constant::DB_COLUMN_OPERATOR, null);
                        $second = data_get($joinItem, Constant::DB_EXECUTION_PLAN_SECOND, null);
                        $type = data_get($joinItem, Constant::DB_COLUMN_TYPE, 'inner');
                        $where = data_get($joinItem, Constant::DB_EXECUTION_PLAN_WHERE, false);
                        $query = $query->join($table, $first, $operator, $second, $type, $where);
                    }
                }

                $query = $query->where($tableAlias . Constant::LINKER . Constant::DB_COLUMN_STATUS, 1)
                    ->limit(1);
            },
        ];

        return [$customizeWhere];
    }

    /**
     * 获取where条件
     * @param array $where
     * @return array
     */
    public static function getWhere($where = [])
    {
        return $where;
    }

    /**
     * 重命名表
     * @param string|ConnectionInterface $connection 数据库连接
     * @param array $tableData ['原表名'=>'重命名后的表名'] 如:['pt_ali_online_products_last' => 'pt_ali_online_products',]
     * @return bool
     * @throws \Throwable
     */
    public static function renameTable(string|ConnectionInterface $connection = Constant::DB_CONNECTION_DEFAULT, array $tableData = []): bool
    {
        if (empty($tableData)) {
            return false;
        }

//        Db::connection($connection)->transaction(function ($_connection) use ($connection, $tableData) {
//            $db = config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . Constant::DATABASE);
//        $renameTableSql = 'RENAME TABLE {toTable} TO {_toTable}';
        $renameTableSql = 'RENAME TABLE {toTable} TO {tmpTable},
             {fromTable} TO {toTable};';
        $dbConnection = ($connection instanceof ConnectionInterface) ? $connection : DB::connection($connection);
        foreach ($tableData as $fromTable => $toTableData) {
//            $_toTable = $toTable . '_' . date('YmdHis');
//            $trans = [
//                '{fromTable}' => $toTable,
//                '{toTable}' => $_toTable,
//            ];
//            $dbConnection->statement(strtr($renameTableSql, $trans));
            $trans = [
                '{toTable}' => data_get($toTableData, 'toTable'),
                '{tmpTable}' => data_get($toTableData, 'tmpTable'),
                '{fromTable}' => $fromTable,
            ];

            $retry = 0;
            beginning:
            try {
                $dbConnection->statement(strtr($renameTableSql, $trans));
            } catch (Throwable $throwable) {

                if ($retry < 10) {
                    $retry = $retry + 1;
                    Coroutine::sleep(rand(2, 5));
                    goto beginning;
                }

                throw $throwable;
            }
        }

//        });

        return true;
    }

    /**
     * 验证表是否存在，返回false则表不存在
     * @param string|ConnectionInterface $connection 数据库连接
     * @param array $tableName ['要创建的表名'=>'模板表名'] 如:['pt_ali_online_products_last' => 'pt_ali_online_products',]
     * @return bool
     * @throws \Throwable
     */
    public static function existsTable(string|ConnectionInterface $connection = Constant::DB_CONNECTION_DEFAULT, string $tableName = ''): bool
    {
        if (empty($tableName)) {
            return false;
        }

        $tableSql = "SHOW TABLES LIKE '{tableName}'";
        $dbConnection = ($connection instanceof ConnectionInterface) ? $connection : DB::connection($connection);
        $trans = [
            '{tableName}' => $tableName,
        ];

        $retry = 0;
        beginning:
        try {
            $result = $dbConnection->select(strtr($tableSql, $trans));
            if (empty($result)) {
                return false;
            }
        } catch (Throwable $throwable) {

            if ($retry < 10) {
                $retry = $retry + 1;
                Coroutine::sleep(rand(2, 5));
                goto beginning;
            }

            throw $throwable;
        }
        return true;
    }

    /**
     * 创建表
     * @param string|ConnectionInterface $connection 数据库连接
     * @param array $tableData ['要创建的表名'=>'模板表名'] 如:['pt_ali_online_products_last' => 'pt_ali_online_products',]
     * @param false $isDrop 是否删除原表 true：是；false：否；默认：false
     * @return bool
     * @throws \Throwable
     */
    public static function createTable(string|ConnectionInterface $connection = Constant::DB_CONNECTION_DEFAULT, array $tableData = [], $isDrop = false): bool
    {
        if (empty($tableData)) {
            return false;
        }

//        Db::connection($connection)->transaction(function ($_connection) use ($connection, $tableData, $isDrop) {
        $createTableSql = 'CREATE TABLE IF NOT EXISTS {fromTable} LIKE {toTable}';
        $dropTableSql = "DROP TABLE IF EXISTS {fromTable}";//
        $dbConnection = ($connection instanceof ConnectionInterface) ? $connection : DB::connection($connection);
        foreach ($tableData as $fromTable => $toTable) {
            $trans = [
                '{fromTable}' => $fromTable,
                '{toTable}' => $toTable,
            ];

            $retry = 0;
            beginning:
            try {
                if ($isDrop) {
                    $dbConnection->statement(strtr($dropTableSql, $trans));
                }
                $dbConnection->statement(strtr($createTableSql, $trans));//, [$fromTable, $toTable]
            } catch (Throwable $throwable) {

                if ($retry < 10) {
                    //创建失败时删除 后再创建
                    $dbConnection->statement(strtr($dropTableSql, $trans));
                    $retry = $retry + 1;
                    Coroutine::sleep(rand(2, 5));
                    goto beginning;
                }

                throw $throwable;
            }


        }

//        });

        return true;
    }

    /**
     * 创建表
     * @param string|ConnectionInterface $connection 数据库连接
     * @param array $tableData 如:['pt_ebay_online_products_20230108091643',...]
     * @return array
     * @throws \Throwable
     */
    public static function dropDbTable(string|ConnectionInterface $connection = Constant::DB_CONNECTION_DEFAULT, array $tableData = []): array
    {
        $rs = [];
        if (empty($tableData)) {
            return $rs;
        }

        $dropTableSql = "DROP TABLE IF EXISTS {table}";//
        $dbConnection = ($connection instanceof ConnectionInterface) ? $connection : DB::connection($connection);

        foreach ($tableData as $table) {
            $trans = [
                '{table}' => $table,
            ];

            $retry = 0;
            beginning:
            try {
                $rs[$table] = $dbConnection->statement(strtr($dropTableSql, $trans));
            } catch (Throwable $throwable) {

                if ($retry < 10) {
                    $retry = $retry + 1;
                    Coroutine::sleep(rand(2, 5));
                    goto beginning;
                }

                $rs[$table] = $throwable;

                go(function () use ($throwable) {
                    throw $throwable;
                });
            }
        }

        return $rs;
    }

    /**
     * 处理数据库配置
     * @param string|array $connection
     * @param string|array $table
     * @return array
     */
    public static function handleDbConfig(string|array $connection = null, string|array $table = null): array
    {
        return call([static::getModelAlias(), 'handleDbConfig'], [$connection, $table]);
    }

    /**
     * 获取分布式锁key
     * @param string|array $connection 数据库连接
     * @param string|array $table 表
     * @param array $lockKeys 扩展key
     * @return string
     */
    public static function getLockKey(string|array $connection, string|array $table, array $lockKeys = [])
    {
        $prefix = "{" . env('APP_NAME', 'skeleton') . "}";
        return strtolower(implode(':', array_filter(
                    Arr::collapse(
                        [
                            [$prefix, 'lock'],
                            is_array($connection) ? $connection : [$connection],
                            is_array($table) ? $table : [$table],
                            $lockKeys
                        ]
                    )
                )
            )
        );
    }

    /**
     * 处理数据库连接和表参数
     * @param array $parameters 数据库连接 默认：default
     * @return bool|int
     */
    public static function handleParameters(string|array $connection, string|array $table)
    {
        $platform = $connection;
        $_table = $table;

        $baseConfig = static::handleDbConfig($connection, $table);
        $connection = data_get($baseConfig, Constant::CONNECTION);
        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        $key = static::getLockKey($connection, $table, [Constant::CACHE_CREATE_TABLE_MARKER]);
//        $expiryTime = config('app.pt.ttl_token');//默认缓存3600秒
        $expiryTime = 86400;//默认缓存 24小时

        $retryCreateTableMarker = 0;
        createTableMarkerBeginning:
        try {
            //创建子任务日志表
            $redis = static::getCacheDriver(Constant::CACHE_CONNECTION_POOL_TASK);
            if (!$redis->has($key)) {
//                loger('sys', 'sys')->debug(sprintf('[' . static::class . '::' . __FUNCTION__ . '] [connection: %s] [table: %s].', $connection, $table));
                $tableData = [
                    config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'prefix') . $table => config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'table_template' . Constant::LINKER . (static::getModelAlias()::TABLE_PREFIX))
                ];
                static::createTable($connection, $tableData, false);

                $redis->set($key, 1, $expiryTime);
            }
        } catch (Throwable $throwable) {
            if ($retryCreateTableMarker < 10) {
                $retryCreateTableMarker = $retryCreateTableMarker + 1;
                Coroutine::sleep(rand(1, 10));
                goto createTableMarkerBeginning;
            }

            go(function () use ($throwable) {
                throw $throwable;
            });
        }

        return $baseConfig;
    }

    /**
     * 添加
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @return \Hyperf\DbConnection\Model\Model|\Hyperf\Database\Model\Relations\Relation|mixed|string|null
     */
    public static function getCurrentModel(string|array $connection, string|array $table)
    {
        $baseConfig = static::handleParameters($connection, $table);
//        $connection = data_get($baseConfig, Constant::CONNECTION);
//        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        $retry = 0;
        beginning:
        try {
            return static::getModel($connection, $table);
        } catch (Throwable $throwable) {

            if ($retry < 10) {
                $retry = $retry + 1;
                Coroutine::sleep(rand(2, 5));
                goto beginning;
            }

            throw $throwable;
        }
    }

    /**
     * 删除表
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @return bool|int
     */
    public static function dropTable(string|array $connection, string|array $table)
    {
        $platform = $connection;
        $_table = $table;

        $baseConfig = static::handleDbConfig($connection, $table);
        $connection = data_get($baseConfig, Constant::CONNECTION);
        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        $tableData = [
            config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'prefix') . $table => config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'table_template' . Constant::LINKER . (static::getModelAlias()::TABLE_PREFIX))
        ];
        static::createTable($connection, $tableData, true);

        $key = static::getLockKey($connection, $table, [Constant::CACHE_CREATE_TABLE_MARKER]);
        $expiryTime = 86400;//默认缓存 24小时
        return static::getCacheDriver(Constant::CACHE_CONNECTION_POOL_TASK)->set($key, 1, $expiryTime);

//        $method = __FUNCTION__;
//        $lockParameters = [
//            function () use ($method, $connection, $table, $platform, $_table) {
////                loger('sys', 'sys')->debug(sprintf('[' . static::class . '::' . $method . '] [connection: %s] [table: %s].', $connection, $table));
//                $tableData = [
//                    config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'prefix') . $table => config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'table_template' . Constant::LINKER . (static::getModelAlias()::TABLE_PREFIX))
//                ];
//                static::createTable($connection, $tableData, true);
//
//                $key = static::getLockKey($connection, $table, [Constant::CACHE_CREATE_TABLE_MARKER]);
//                $expiryTime = config('app.pt.ttl_token');//默认缓存3600秒
//                return static::getCacheDriver(Constant::CACHE_CONNECTION_POOL_TASK)->set($key, 1, $expiryTime);
//            }
//        ];
//
//        return static::handleLock([static::getLockKey($connection, $table, [$method])], $lockParameters);

    }

    /**
     * 清空表
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @return mixed
     * @throws \Throwable
     */
    public static function truncate(string|array $connection, string|array $table)
    {
        $retryTruncate = 0;
        truncateBeginning:
        try {
            return static::getCurrentModel($connection, $table)->truncate();
        } catch (Throwable $throwable) {
            if ($retryTruncate < 10) {
                $retryTruncate = $retryTruncate + 1;
                Coroutine::sleep(rand(1, 10));
                goto truncateBeginning;
            }

            throw $throwable;
        }

//        $method = __FUNCTION__;
//        $lockParameters = [
//            function () use ($method, $connection, $table) {
////                loger('sys', 'sys')->debug(sprintf('[' . static::class . '::' . $method . '] [connection: %s] [table: %s].', $connection, static::pack($table)));
//
//                $retryTruncate = 0;
//                truncateBeginning:
//                try {
//                    return static::getCurrentModel($connection, $table)->truncate();
//                } catch (\Throwable $throwable) {
//                    if ($retryTruncate < 10) {
//                        $retryTruncate = $retryTruncate + 1;
//                        Coroutine::sleep(rand(1, 10));
//                        goto truncateBeginning;
//                    }
//
//                    throw $throwable;
//                }
//            }
//        ];
//
//        $baseConfig = static::handleDbConfig($connection, $table);
//        $connection = data_get($baseConfig, Constant::CONNECTION);
//        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);
//
//        return static::handleLock([static::getLockKey($connection, $table, [$method])], $lockParameters);

    }

    /**
     * 添加
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @param array $data 数据
     * @param bool|null $isGetId 是否返回 id true:是 false:否
     * @return bool|int
     */
    public static function insertData(string|array $connection, string|array $table, array $data, ?bool $isGetId = false)
    {
        $baseConfig = static::handleParameters($connection, $table);
//        $connection = data_get($baseConfig, Constant::CONNECTION);
//        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        return static::insert($data, $isGetId, $connection, $table);
    }

    /**
     * 更新
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @param string|array $where 更新的条件
     * @param array $data 更新的数据
     * @return bool
     */
    public static function updateData(string|array $connection, string|array $table, string|array $where, array $data, ?array $handleData = [])
    {
        $baseConfig = static::handleParameters($connection, $table);
//        $connection = data_get($baseConfig, Constant::CONNECTION);
//        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        return static::update($where, $data, $handleData, $connection, $table);
    }

    /**
     * 删除
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @param string|array $where 删除条件
     * @return bool
     */
    public static function deleteData(string|array $connection, string|array $table, string|array $where, ?array $handleData = [])
    {
        $baseConfig = static::handleParameters($connection, $table);
//        $connection = data_get($baseConfig, Constant::CONNECTION);
//        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        return static::delete($where, $handleData, $connection, $table); //逻辑删除
    }

    /**
     * 更新或者新增记录
     * @param string|null $connection 数据库连接 默认：default
     * @param string|null $table 表名 默认使用model配置的表名
     * @param array $where where条件
     * @param array $data 数据
     * @param array|null $handleData 执行数据库操作前必须通过的校验
     * @return array [
     *        'lock' => $lock,
     *        'dbOperation' => data_get($rs, 'dbOperation', 'no'),
     *        'data' => $rs,
     *    ];
     */
    public static function updateOrCreateData(string|array $connection, string|array $table, string|array $where, array $data, ?array $handleData = [])
    {
        $baseConfig = static::handleParameters($connection, $table);
//        $connection = data_get($baseConfig, Constant::CONNECTION);
//        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        return static::updateOrCreate($where, $data, $handleData, $connection, $table); //更新或者新增记录
    }

}
