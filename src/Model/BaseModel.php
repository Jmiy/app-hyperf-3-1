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

namespace Business\Hyperf\Model;

use function Hyperf\Support\make;
use function Hyperf\Config\config;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Arrays\MyArr;
use Hyperf\DbConnection\Db;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Relations\Relation;

/**
 * 批量生成model：php bin/hyperf.php gen:model --pool=default
 * 生成指定的model：php bin/hyperf.php gen:model table_name --pool=default
 * Class BaseModel
 * @package Business\Hyperf\Model
 */
class BaseModel extends Model
{
    /**
     * The connection name for the model.
     * 数据库连接
     * 默认情况下，所有的 Eloquent 模型会使用应用程序中默认的数据库连接设置。如果你想为模型指定不同的连接，可以使用 $connection 属性：
     *
     * @var string
     */
    //protected $connection;

    /**
     * 不可被批量赋值的属性。
     * $guarded 属性包含的是不想被批量赋值的属性的数组。即所有不在数组里面的属性都是可以被批量赋值的。也就是说，$guarded 从功能上讲更像是一个「黑名单」。而在使用的时候，也要注意只能是 $fillable 或 $guarded 二选一
     * 如果想让所有的属性都可以被批量赋值，就把 $guarded 定义为空数组。
     *
     * @var array
     */
//    protected array $guarded = [];

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    public const CREATED_AT = null;

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    public const UPDATED_AT = null;

    /**
     * Indicates if the model should be timestamped.
     * 时间戳
     * 默认情况下，Eloquent 会认为在你的数据库表有 created_at 和 updated_at 字段。如果你不希望让 Eloquent 来自动维护这两个字段，可在模型内将 $timestamps 属性设置为 false
     *
     * @var bool
     */
    /**
     * Indicates if the model should be timestamped.
     */
    public bool $timestamps = true;

    /**
     * The storage format of the model's date columns.
     */
    protected ?string $dateFormat = 'U';

    /**
     * The storage format of the model's DELETED_AT date columns.
     */
    public const DELETED_AT_DATE_FORMAT = 'U';
    public const STATUS_COLUMN = null;//数据状态字段 默认：无
    public const DELETED_AT = null;//数据状态变更时间字段 默认：无
    public const EFFECTIVE = 0;//数据有效
    public const NO_EFFECTIVE = 1;//数据无效
    public const TABLE_ALIAS = null;//表别名
    public const TABLE_PREFIX = null;//表前缀
    public const TABLE_SUFFIX = null;//表后缀
    public const CONNECTION_PREFIX = null;//数据库连接前缀
    public const CONNECTION_SUFFIX = null;//数据库连接后缀

    public $morphToConnection = [];

    public function setMorphToConnection($morphToConnection = [])
    {
        $this->morphToConnection = Arr::collapse([$this->morphToConnection, $morphToConnection]);
        return $this;
    }

    public function getMorphToConnection()
    {
        return $this->morphToConnection;
    }

    public static function setConfig(?string $connection = Constant::DB_CONNECTION_DEFAULT, ?array $dbConfig = [])
    {
        $key = Constant::DATABASES . Constant::LINKER . $connection;
        $config = getConfig();
        if (!$config->has($key)) {//如果 $connection 对应的数据库连接配置不在数据库配置文件中，就创建 $connection 对应的数据库连接配置到数据库配置文件

//            throw new \RuntimeException('db connection:' . $key . ' ' . json_encode(func_get_args()), 999999999);

            $dbConfig = Arr::collapse(
                [
                    config(Constant::DATABASES . Constant::LINKER . Constant::DB_CONNECTION_DEFAULT, []),
                    $dbConfig
                ]
            );
            $dbConfig['cache']['prefix'] = $connection;

            $config->set($key, $dbConfig);
        }

        return true;

    }

    /**
     * 生成model
     * @param string|null $connection 数据库连接
     * @param string|null $make 模型别名
     * @param array|null $parameters 参数
     * @param string|null $table 表名 默认使用model配置的表名
     * @param Relation|null $relation 关联对象
     * @param array|null $dbConfig 数据库配置
     * @return Relation|string|static|null
     */
    public static function createModel(?string $connection = Constant::DB_CONNECTION_DEFAULT, ?string $make = null, ?array $parameters = [], ?string $table = null, ?Relation &$relation = null, ?array $dbConfig = []): static|Relation|string|null
    {

        if ($make === null && $relation === null) {
            return null;
        }

        if (false !== strpos($connection, Constant::DB_EXECUTION_PLAN_DEFAULT_CONNECTION)) {//如果使用 model 默认数据库连接，就直接创建model对象即可

            if ($relation instanceof Relation) {
                return $relation;
            }

            if ($make) {
                //model禁止使用单例，否则数据库操作会导致混乱
                $model = make($make, $parameters);
                if ($table) {
                    $model->setTable($table);
                }
                return $model;
            }

            return null;
        }

        //设置数据库连接池
        static::setConfig($connection, $dbConfig);

        if ($relation instanceof Relation) {
            /**
             * Get the underlying query for the relation. $relation->getQuery()
             *
             * @return \Hyperf\Database\Model\Builder
             */
            //设置数据库连接
            $relation->getRelated()->setConnection($connection);

            //设置关联对象relation 数据库连接
            /**
             * $relation->getBaseQuery() $relation->getRelated()->getQuery()
             * Get the base query builder driving the Eloquent builder.
             *
             * @return \Hyperf\Database\Query\Builder
             */
            $relation->getBaseQuery()->connection = $relation->getRelated()->getQuery()->connection;

            return $relation;
        }

        if ($make) {
            $model = make($make, $parameters);//model禁止使用单例，否则数据库操作会导致混乱
            $model->setConnection($connection);
            if ($table) {
                $model->setTable($table);
            }

            return $model;
        }

        return $relation;
    }

    /**
     * 模型的默认属性值。
     *
     * @var array
     */
//    protected $attributes = [
//        'delayed' => false,
//    ];

    /**
     * 构建where
     * eg：$where = [
     * 'u.id' => [1, 2, 3],
     * 'or' => [
     * 'u.id' => 4,
     * 'u.name1' => 5,
     * [
     * ['u.id', '=', 10],
     * ['u.id', '=', 11]
     * ],
     * [['u.id', 'like', '%55%']],
     * [['u.username', 'like', '%55%']],
     * ],
     * [
     * ['u.id', '=', 6],
     * ['u.id', '=', 7]
     * ],
     * 'u.username' => '565',
     * 'u.username' => DB::raw('password'),
     * 'u.a=kkk',
     * ];
     * //->onlyTrashed()  withTrashed
     * $query = \Business\Hyperf\Model\User::from('user as u')->withoutTrashed()->buildWhere($where)
     * ->leftJoin('user_roles as b', function ($join) {
     * $join->on('b.user_id', '=', 'u.id'); //->where('b.status', '=', 1);
     * })
     * ;
     * @param Builder $query
     * @param array $where where条件
     * @param string $boolean 布尔运算符
     * @param boolean $getSql 是否获取sql
     * @return Builder|array $query
     */
    public function scopeBuildWhere($query, $where = [], $boolean = 'and', $getSql = false): Builder|array
    {
        foreach ($where as $column => $value) {

            if (is_string($column)) {
                if ($column === Constant::DB_EXECUTION_PLAN_CUSTOMIZE_WHERE) {//自定义where
                    foreach ($value as $customizeWhereItem) {
                        $method = data_get($customizeWhereItem, 'method', '');
                        $parameters = data_get($customizeWhereItem, 'parameters', []);
                        if (is_array($parameters)) {
                            $query->{$method}(...$parameters);
                        } else {
                            $query->{$method}($parameters);
                        }
                    }
                } else {
                    if (is_array($value)) {

                        $method = 'where';
                        if ($boolean == 'or') {
                            $method = 'OrWhere';
                        }

                        $query->{$method}(function ($query) use ($column, $value, $boolean) {
                            if (MyArr::isIndexedArray($value) && !is_array(Arr::first($value))) {
//                                foreach ($value as $item) {
//                                    $query->OrWhere($column, '=', $item);
//                                }
                                $query->whereIn($column, $value);
                            } elseif (MyArr::isAssocArray($value)) {
                                $boolean = $column;
                                $operator = '=';

                                foreach ($value as $_column => $item) {
                                    if (is_array($item)) {
                                        $this->scopeBuildWhere($query, [$_column => $item], $boolean);
                                    } else {
                                        $query->where($_column, $operator, $item, $boolean);
                                    }

                                }
                            } else {
                                $this->scopeBuildWhere($query, $value, $column);
                            }
                        });
                    } else {
                        $query->where($column, '=', $value, $boolean);
                    }
                }

                continue;
            }

            if (is_string($value)) {
                $query->whereRaw($value, [], $boolean);
            } else {
                $query->where($value, null, null, $boolean);
            }
        }

//        if ($getSql) {
//            $query->getConnection()->enableQueryLog();
//            $query->getConnection()->getQueryLog();
//        }

        return $getSql ? ['query' => $query->toSql(), 'bindings' => $query->getBindings(), 'time'] : $query; //->toSql() ->dump() ->dd()
    }

    /**
     * 处理数据库连接池和表名
     * @param string|array $connection 数据库连接
     * @param string|array $table 表
     * @return array
     */
    public static function handleDbConfig(string|array $connection = null, string|array $table = null)
    {
        $separator = '_';
        $connection = strtolower(implode($separator, array_filter(
                Arr::collapse(
                    [
                        [static::CONNECTION_PREFIX ? trim(static::CONNECTION_PREFIX, $separator) : static::CONNECTION_PREFIX],
                        (is_array($connection) ? $connection : [$connection]),
                        [static::CONNECTION_SUFFIX ? trim(static::CONNECTION_SUFFIX, $separator) : static::CONNECTION_SUFFIX],
                    ]
                )
            )
        ));

        if (isset($table['table']) && $table['table']) {
            $table = implode('', array_filter(
                    Arr::collapse(
                        [
                            [($table['prefix'] ?? config('databases.' . $connection . '.prefix'))],
                            (is_array($table['table']) ? $table['table'] : [$table['table']]),
                        ]
                    )
                )
            );
        } else {
            $table = strtolower(implode($separator, array_filter(
                    Arr::collapse(
                        [
                            [static::TABLE_PREFIX ? trim(static::TABLE_PREFIX, $separator) : static::TABLE_PREFIX],
                            (is_array($table) ? $table : [$table]),
                            [static::TABLE_SUFFIX ? trim(static::TABLE_SUFFIX, $separator) : static::TABLE_SUFFIX],
                        ]
                    )
                )
            ));
        }


        return [
            Constant::CONNECTION => $connection,
            Constant::DB_EXECUTION_PLAN_TABLE => $table,
        ];
    }

    /**
     * 删除表
     * @param string|array $connection 数据库连接 默认：default
     * @param string|array $table 表名 默认使用model配置的表名
     * @return bool|int
     */
    public static function dropTable(string $connection, string $table)
    {
        loger('sys', 'sys')->debug(sprintf('[' . static::class . '::' . __FUNCTION__ . '] [connection: %s] [table: %s].', $connection, $table));
        $dropTableSql = "DROP TABLE IF EXISTS {$table};";
        return Db::connection($connection)->statement($dropTableSql);
    }
}
