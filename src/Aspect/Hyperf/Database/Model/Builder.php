<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.wiki/3.0/#/zh-cn/aop
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Aspect\Hyperf\Database\Model;

use function Hyperf\Collection\data_set;
use function Hyperf\Support\call;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Tappable\tap;
use Business\Hyperf\Constants\Constant;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\Utils\Coroutine;

#[Aspect(classes: [ModelBuilder::class . '::__call', ModelBuilder::class . '::firstOrCreate', ModelBuilder::class . '::updateOrCreate', ModelBuilder::class . '::update', ModelBuilder::class . '::firstOrNew'], annotations: [])]
class Builder extends AbstractAspect
{
    public static $dbOperation = [
        0 => Constant::DB_OPERATION_SELECT,
        1 => Constant::DB_OPERATION_INSERT,
        2 => Constant::DB_OPERATION_UPDATE,
        3 => Constant::DB_OPERATION_DELETE,
    ];

    /**
     * 获取modelClass
     * @return type
     */
    public function getModelClass(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $instance = $proceedingJoinPoint->getInstance();
        return get_class($instance->getModel());
    }

    /**
     * 获取model属性数据
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @param array $values
     * @param string $dbOperation
     * @return array
     */
    public function getAttributesData(ProceedingJoinPoint $proceedingJoinPoint, array $values, string $dbOperation = 'insert')
    {

        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return $values;
        }

        $modelClass = $this->getModelClass($proceedingJoinPoint);

        if (is_array(reset($values))) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->getAttributesData($proceedingJoinPoint, $value, $dbOperation);
            }
            return $values;
        }

        date_default_timezone_set('Asia/Shanghai'); //设置app时区 https://www.php.net/manual/en/timezones.php
        $model = $proceedingJoinPoint->getInstance()->getModel();
        $timestamps = $model->timestamps;

        $nowTime = $model->freshTimestampString();
        switch ($dbOperation) {
            case data_get(static::$dbOperation, 1, null):
                if ($timestamps && !$model->exists && !is_null($model->getCreatedAtColumn()) && !$model->isDirty($model->getCreatedAtColumn())) {
                    data_set($values, $modelClass::CREATED_AT, $nowTime, false);
                }

//                if (!isset($values[$this->getModel()->getKeyName()])) {
//                    $container = ApplicationContext::getContainer();
//                    $generator = $container->get(IdGeneratorInterface::class);
//                    data_set($values, $this->getModel()->getKeyName(), $generator->generate(), false);
//                }

                break;

            default:
                break;
        }

        if ($timestamps && !is_null($model->getUpdatedAtColumn()) && !$model->isDirty($model->getUpdatedAtColumn())) {
            data_set($values, $modelClass::UPDATED_AT, $nowTime, false);
        }

        return $values;
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $values
     * @return \Hyperf\Database\Model\Model|static
     */
    public function aop_firstOrCreate(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $_instance = $proceedingJoinPoint->getInstance();

        $attributes = data_get($proceedingJoinPoint->arguments, 'keys.attributes', []);
        $values = data_get($proceedingJoinPoint->arguments, 'keys.values', []);

        if (!is_null($instance = $_instance->where($attributes)->first())) {
            data_set($instance, Constant::DB_OPERATION, data_get(static::$dbOperation, 0, null));
            return $instance;
        }

        $dbOperation = data_get(static::$dbOperation, 1, null);

        return tap($_instance->newModelInstance($attributes + $this->getAttributesData($proceedingJoinPoint, $values, $dbOperation)), function ($instance) use ($dbOperation) {
            $instance->save();
            data_set($instance, Constant::DB_OPERATION, $dbOperation);
        });
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return \Hyperf\Database\Model\Model|static
     */
    public function aop_firstOrNew(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $_instance = $proceedingJoinPoint->getInstance();

        $attributes = data_get($proceedingJoinPoint->arguments, 'keys.attributes', []);
        $values = data_get($proceedingJoinPoint->arguments, 'keys.values', []);

        if (!is_null($instance = $_instance->buildWhere($attributes)->first())) {//->getModel()
            return $instance;
        }

        if (isset($attributes[Constant::DB_EXECUTION_PLAN_CUSTOMIZE_WHERE])) {
            unset($attributes[Constant::DB_EXECUTION_PLAN_CUSTOMIZE_WHERE]);
        }

        return $_instance->newModelInstance($attributes + $values);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return \Hyperf\Database\Model\Model|static
     */
    public function aop_updateOrCreate(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $_instance = $proceedingJoinPoint->getInstance();

        $parameters = $proceedingJoinPoint->getArguments();

        $attributes = data_get($parameters, 0, []);//where
        $values = data_get($parameters, 1, []);//data

        $handleData = [];
        if (isset($attributes['handleData'])) {
            $handleData = $attributes['handleData'];
            unset($attributes['handleData']);
        }

        return tap($_instance->firstOrNew($attributes), function ($instance) use ($proceedingJoinPoint, $values, $handleData) {

            $dbOperation = data_get(static::$dbOperation, ($instance->exists ? 2 : 1), null);

            $srcInstance = clone $instance; //克隆原始model实例

            if (empty($instance->exists)) {

                $isInsert = true;
                $insertHandle = data_get($handleData, Constant::DB_OPERATION_INSERT, []);
                foreach ($insertHandle as $func) {
                    $isInsert = call($func, [$srcInstance, &$values]);
                }

                if (!$isInsert) {
                    $dbOperation = data_get(static::$dbOperation, 0, null);
                    data_set($instance, Constant::DB_OPERATION, $dbOperation, false); //设置数据库操作
                    return $instance;
                }

                $values = $this->getAttributesData($proceedingJoinPoint, $values, $dbOperation);
            }

            $instance->fill($values); //Fill the model with an array of attributes. 比较要更新的字段的值是否有更新，并且把最新的值更新到model实例对应的字段属性
            if ($instance->exists) {
                if (empty($instance->getDirty())) {//Get the attributes that have been changed since last sync. 如果没有更新，数据库操作dbOperation：select 并且直接返回查询结果
                    $dbOperation = data_get(static::$dbOperation, 0, null);
                } else {//如果有更新，数据库操作dbOperation：update 更新数据库的更新时间，并且返回更新以后的结果
                    $isUpdate = true;
                    $updateHandle = data_get($handleData, Constant::DB_OPERATION_UPDATE, []);
                    foreach ($updateHandle as $func) {
                        $isUpdate = call($func, [$srcInstance, &$values]);
                    }

                    if (!$isUpdate) {
                        $dbOperation = data_get(static::$dbOperation, 0, null);
                        $instance->fill($srcInstance->toArray());
                        data_set($instance, Constant::DB_OPERATION, $dbOperation, false); //设置数据库操作
                        return $instance;
                    }

                    $values = $this->getAttributesData($proceedingJoinPoint, $values, $dbOperation);
                    $instance->fill($values);
                }
            }

            $instance->save();

            data_set($instance, Constant::DB_OPERATION, $dbOperation, false); //设置数据库操作
        });
    }

    /**
     * Update a record in the database.
     *
     * @param array $values
     * @return int
     */
    public function aop_update(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $values = data_get($proceedingJoinPoint->arguments, 'keys.values', []);
        data_set($proceedingJoinPoint->arguments, 'keys.values', $this->getAttributesData($proceedingJoinPoint, $values, data_get(static::$dbOperation, 2, null)));
        return $proceedingJoinPoint->process();
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function aop___call(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $method = data_get($proceedingJoinPoint->arguments, 'keys.method', '');
        $parameters = data_get($proceedingJoinPoint->arguments, 'keys.parameters', []);

        switch ($method) {
            case 'insert':
            case 'insertGetId':
                data_set($parameters, '0', $this->getAttributesData($proceedingJoinPoint, data_get($parameters, '0', []), data_get(static::$dbOperation, 1, null)));
                data_set($proceedingJoinPoint->arguments, 'keys.parameters', $parameters);
                break;

            default:
                break;
        }

        return $proceedingJoinPoint->process();
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return call([$this, "aop_" . $proceedingJoinPoint->methodName], [$proceedingJoinPoint]);
    }

}
