<?php

declare(strict_types=1);

namespace Business\Hyperf\Model\Log;

use App\Constants\Constant;
use Business\Hyperf\Model\BaseModel;

/**
 * @property int $id 主键id
 * @property string $code code
 * @property string $message message
 * @property string $file file
 * @property int $line line
 * @property string $business_data business_data
 * @property string $stack_trace stack_trace
 * @property \Carbon\Carbon $create_time create_time
 * @property \Carbon\Carbon $update_time update_time
 */
class Log extends BaseModel
{
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    public const CREATED_AT = 'create_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    public const UPDATED_AT = 'update_time';

    public const TABLE_PREFIX = 'log';//表前缀
    public const CONNECTION_PREFIX = Constant::DB_CONNECTION_PREFIX;//'pt_listing';//数据库连接前缀

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'log';

    /**
     * The connection name for the model.
     */
    protected ?string $connection = 'bluelans_pt_listing_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'code', 'message', 'file', 'line', 'business_data', 'stack_trace', 'create_time', 'update_time'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'line' => 'integer', 'create_time' => 'datetime', 'update_time' => 'datetime'];
}
