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

namespace Hyperf\Database\Model;

use Hyperf\DbConnection\Db;

class SoftDeletingScope implements Scope
{
    /**
     * All the extensions to be added to the builder.
     */
    protected array $extensions = ['Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    /**
     * Apply the scope to a given Model query builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     * @param \Hyperf\Database\Model\Model $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedDeletedAtColumn($builder), '=', $model::EFFECTIVE);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);

            $model = $builder->getModel();
            $data = [
                $column => Db::raw($model->getKeyName()),//设置为无效
            ];
            if ($model->getDeletedTimeColumn()) {
                $data[$model->getDeletedTimeColumn()] = $model->getDateTime($model->freshTimestamp(), $model::DELETED_AT_DATE_FORMAT);
            }

            return $builder->update($data);
        });
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder)
    {
        if (count((array)$builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedAtColumn($builder);
        }

        return $builder->getModel()->getDeletedAtColumn();
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            $model = $builder->getModel();
            $column = $model->getDeletedAtColumn();

            $data = [$column => $model::EFFECTIVE];
            if ($model->getDeletedTimeColumn()) {
                $data[$model->getDeletedTimeColumn()] = $model->getDateTime($model->freshTimestamp(), $model::DELETED_AT_DATE_FORMAT);
            }

            return $builder->update($data);
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addWithTrashed(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder, $withTrashed = true) {
            if (!$withTrashed) {
                return $builder->withoutTrashed();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedAtColumn($builder), '=', $model::EFFECTIVE
            );

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedAtColumn($builder), '!=', $model::EFFECTIVE
            );

            return $builder;
        });
    }
}
