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

use function Hyperf\Tappable\tap;
use Hyperf\DbConnection\Db;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * @method static static|\Hyperf\Database\Model\Builder|\Hyperf\Database\Query\Builder withTrashed(bool $withTrashed = true)
 * @method static static|\Hyperf\Database\Model\Builder|\Hyperf\Database\Query\Builder onlyTrashed()
 * @method static static|\Hyperf\Database\Model\Builder|\Hyperf\Database\Query\Builder withoutTrashed()
 */
trait SoftDeletes
{
    /**
     * Indicates if the model is currently force deleting.
     */
    protected bool $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope());
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return null|bool
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;

        return tap($this->delete(), function ($deleted) {
            $this->forceDeleting = false;

            if ($deleted) {
                $this->fireModelEvent('forceDeleted');
            }
        });
    }

    /**
     * Get the format for database stored dates.
     */
    public function getDateTimeFormat($dateFormat = null): string
    {
        return $dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @return null|string
     */
    public function getDateTime(mixed $value, $dateFormat = null): mixed
    {
        return empty($value) ? $value : $this->asDateTime($value)->format(
            $this->getDateTimeFormat($dateFormat)
        );
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return null|bool
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($event = $this->fireModelEvent('restoring')) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                return false;
            }
        }

        //更新还原时间
        $time = $this->freshTimestamp();
        if ($this->getDeletedTimeColumn()) {
            $this->{$this->getDeletedTimeColumn()} = $this->getDateTime($time, static::DELETED_AT_DATE_FORMAT);
        }

        if ($this->getDeletedAtColumn()) {
            $this->{$this->getDeletedAtColumn()} = static::EFFECTIVE;
        }

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored');

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return static::EFFECTIVE != $this->{$this->getDeletedAtColumn()};
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::STATUS_COLUMN') ? static::STATUS_COLUMN : 'status';
    }

    /**
     * Get the name of the "deleted time" column.
     *
     * @return string
     */
    public function getDeletedTimeColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : '';
    }

    /**
     * Get the fully qualified "deleted at" column.
     * @param \Hyperf\Database\Model\Builder $builder
     * @return string
     */
    public function getQualifiedDeletedAtColumn(Builder $builder = null)
    {
        $column = $this->getDeletedAtColumn();

        if ($builder !== null) {
            $from = $builder->getQuery()->from;
            if (stripos($from, ' as ') !== false) {
                $segments = preg_split('/\s+as\s+/i', $from);

                $column = end($segments) ? (end($segments) . '.' . $column) : $column;
            }
        }

        return $this->qualifyColumn($column);
    }

    /**
     * Perform the actual delete query on this model instance.
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            $this->exists = false;

            return $this->newModelQuery()->where($this->getKeyName(), $this->getKey())->forceDelete();
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     */
    protected function runSoftDelete()
    {
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        $time = $this->freshTimestamp();

        $columns = [];
        if ($this->getDeletedAtColumn()) {
            $columns[$this->getDeletedAtColumn()] = Db::raw($this->getKeyName());//设置为无效
            $this->{$this->getDeletedAtColumn()} = $this->getKey();//设置为无效
        }

        //更新删除时间
        if ($this->getDeletedTimeColumn()) {
            $columns[$this->getDeletedTimeColumn()] = $this->getDateTime($time, static::DELETED_AT_DATE_FORMAT);
            $this->{$this->getDeletedTimeColumn()} = $this->getDateTime($time, static::DELETED_AT_DATE_FORMAT);
        }


        if ($this->timestamps && !is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);
    }
}
