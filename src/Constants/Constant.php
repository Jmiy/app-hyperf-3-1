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

namespace Business\Hyperf\Constants;

class Constant
{
    public const PAGE = 'page';
    public const PAGE_SIZE = 'page_size';
    public const TOTAL = 'total';
    public const TOTAL_PAGE = 'total_page';

    public const SERVICE = 'service';
    public const METHOD = 'method';
    public const PARAMETERS = 'parameters';

    public const CODE = 'code';
    public const CODE_SUCCESS = 200;//响应成功状态码
    public const CODE_FAILURE = 0;//响应失败默认状态码
    public const MSG = 'msg';
    public const DATA = 'data';
    public const EXE_TIME = 'exeTime';
    public const REQUEST_DATA = 'requestData';

    public const DB_EXECUTION_PLAN_PARENT = 'parent';
    public const DB_EXECUTION_PLAN_SETCONNECTION = 'setConnection';
    public const DB_EXECUTION_PLAN_STOREID = 'storeId';
    public const DB_EXECUTION_PLAN_RELATION = 'relation';
    public const DB_EXECUTION_PLAN_BUILDER = 'builder';
    public const DB_EXECUTION_PLAN_MAKE = 'make';
    public const DB_EXECUTION_PLAN_FROM = 'from';
    public const DB_EXECUTION_PLAN_SELECT = 'select';
    public const DB_EXECUTION_PLAN_WHERE = 'where';
    public const DB_EXECUTION_PLAN_LIMIT = 'limit';
    public const DB_EXECUTION_PLAN_OFFSET = 'offset';
    public const DB_EXECUTION_PLAN_ORDERS = 'orders';
    public const DB_EXECUTION_PLAN_GROUPBY = 'groupBy';
    public const DB_EXECUTION_PLAN_IS_PAGE = 'isPage';
    public const DB_EXECUTION_PLAN_PAGINATION = 'pagination';
    public const DB_EXECUTION_PLAN_IS_ONLY_GET_COUNT = 'isOnlyGetCount';
    public const DB_EXECUTION_PLAN_HANDLE_DATA = 'handleData';
    public const DB_EXECUTION_PLAN_FIELD = 'field';
    public const DB_EXECUTION_PLAN_DATATYPE = 'dataType';
    public const DB_EXECUTION_PLAN_DATA_FORMAT = 'dateFormat';
    public const DB_EXECUTION_PLAN_GLUE = 'glue';
    public const DB_EXECUTION_PLAN_DEFAULT = 'default';
    public const DB_EXECUTION_PLAN_TIME = 'time';
    public const DB_EXECUTION_PLAN_IS_ALLOW_EMPTY = 'is_allow_empty';
    public const DB_EXECUTION_PLAN_UNSET = 'unset';
    public const DB_EXECUTION_PLAN_WITH = 'with';
    public const DB_EXECUTION_PLAN_ITEM_HANDLE_DATA = 'itemHandleData';
    public const DB_EXECUTION_PLAN_CALLBACK = 'callback';
    public const DB_EXECUTION_PLAN_DEBUG = 'sqlDebug';
    public const DB_EXECUTION_PLAN_JOIN_DATA = 'joinData';
    public const DB_EXECUTION_PLAN_TABLE = 'table';
    public const DB_EXECUTION_PLAN_FIRST = 'first';
    public const DB_EXECUTION_PLAN_SECOND = 'second';
    public const DB_EXECUTION_PLAN_ONLY = 'only';
    public const DB_EXECUTION_PLAN_IS_ONLY_GET_PRIMARY = 'isOnlyGetPrimary';
    public const DB_EXECUTION_PLAN_DEFAULT_CONNECTION = 'default_connection_';
    public const DB_EXECUTION_PLAN_ORDER_DESC = 'DESC';
    public const DB_EXECUTION_PLAN_ORDER_ASC = 'ASC';
    public const DB_OPERATION = 'dbOperation';
    public const DB_OPERATION_SELECT = 'select';
    public const DB_OPERATION_INSERT = 'insert';
    public const DB_OPERATION_UPDATE = 'update';
    public const DB_OPERATION_DELETE = 'delete';
    public const DB_OPERATION_UPDATE_OR_CREATE = 'updateOrCreate';
    public const DB_OPERATION_DEFAULT = 'no';

    public const DATABASES = 'databases';
    public const DATABASE = 'database';
    public const DB_CONNECTION_DEFAULT = 'default';
    public const DB_CONNECTION_PREFIX = 'bluelans_chatgpt_';
    public const DB_CONNECTION_PREFIX_APP = 'bluelans_chatgpt_app_';
    public const DB_COLUMN_CREATED_AT = 'create_time';
    public const DB_COLUMN_UPDATED_AT = 'update_time';
    public const DB_COLUMN_STATUS = 'status';
    public const DB_COLUMN_IS_DELETED = 'is_deleted';
    public const DB_COLUMN_DELETED_AT = 'deleted_time';

    public const DB_COLUMN_PARENT_ID = 'parent_id';
    public const DB_COLUMN_ROLE_ID = 'role_id';
    public const DB_COLUMN_DATA_PERMISSION_ID = 'data_permission_id';
    public const DB_COLUMN_DATAS_PRIV = 'datas_priv';
    public const DB_COLUMN_ADMIN_ID = 'admin_id';
    public const DB_COLUMN_USER_ID = 'user_id';
    public const DB_COLUMN_IS_MASTER = 'is_master';
    public const DB_COLUMN_DBHOST = 'dbhost';
    public const DB_COLUMN_CODENO = 'codeno';
    public const DB_COLUMN_MENU_ID = 'menu_id';
    public const DB_COLUMN_NEW_MENU_ID = 'new_menu_id';
    public const DB_COLUMN_ROLE_NAME = 'role_name';
    public const DB_COLUMN_DESCRIPTION = 'description';
    public const DB_COLUMN_IS_CPC_PROXY_OPERATOR_ROLE = 'is_cpc_proxy_operator_role';

    public const DB_COLUMN_STATE = 'state';
    public const DB_COLUMN_STATE_INVITED = 'invited';
    public const DB_COLUMN_STATE_DISABLED = 'disabled';//disabled/invited/enabled/declined
    public const DB_COLUMN_STATE_ENABLED = 'enabled';
    public const DB_COLUMN_STATE_DECLINED = 'declined';
    public const DB_COLUMN_INVITE_CODE = 'invite_code';
    public const DB_COLUMN_NEW_INVITE_CODE = 'new_invite_code';
    public const DB_COLUMN_INVITE_ACCOUNT = 'invite_account';//被邀请者账号
    public const DB_COLUMN_INVITE_COMMISSION = 'commission';//邀请的奖励
    public const DB_COLUMN_INVITE_TYPE = 'invite_code_type';//邀请码类型

    public const DB_COLUMN_PLATFORM = 'platform';
    public const DB_COLUMN_PLATFORM_ID = 'platform_id';
    public const DB_COLUMN_ACCOUNT = 'account';
    public const DB_COLUMN_ACCOUNT_ID = 'account_id';
    public const DB_COLUMN_ACCOUNT_INFO = 'account_info';
    public const DB_COLUMN_ACCOUNT_DETAILS = 'details_info';
    public const DB_COLUMN_SITE_ID = 'site_id';
    public const DB_COLUMN_SITE_INFO = 'site_info';
    public const DB_COLUMN_CUSTOMER_PRIMARY = 'customer_id';
    public const DB_COLUMN_COUNTRY = 'country';
    public const DB_COLUMN_ACT_ID = 'act_id';
    public const DB_COLUMN_STORE_CUSTOMER_ID = 'store_customer_id';
    public const DB_COLUMN_FIRST_NAME = 'first_name';
    public const DB_COLUMN_LAST_NAME = 'last_name';
    public const DB_COLUMN_GENDER = 'gender';
    public const DB_COLUMN_BRITHDAY = 'brithday';
    public const DB_COLUMN_SOURCE = 'source';
    public const DB_COLUMN_LASTLOGIN = 'lastlogin';
    public const DB_COLUMN_LAST_SYS_AT = 'last_sys_at';
    public const DB_COLUMN_IP = 'ip';
    public const DB_COLUMN_ORDER_NO = 'orderno';
    public const DB_COLUMN_EMAIL = 'email';
    public const DB_COLUMN_PASSWORD = 'password';

    public const DB_COLUMN_ACTIVITY_ID = 'activity_id';
    public const DB_COLUMN_TYPE = 'type';
    public const DB_COLUMN_KEY = 'key';
    public const DB_COLUMN_VALUE = 'value';
    public const DB_COLUMN_TYPE_VALUE = 'type_value';

    public const DB_COLUMN_REGION = 'region';
    public const DB_COLUMN_CITY = 'city';
    public const DB_COLUMN_STREET = 'street';
    public const DB_COLUMN_ADDR = 'addr';
    public const DB_COLUMN_ADDRESS = 'address';
    public const DB_COLUMN_ADDRESSES = 'addresses';
    public const DB_COLUMN_PLATFORM_ADDRESSES = 'platform_addresses';

    public const DB_COLUMN_PRIMARY = 'id';
    public const DEFAULT_PRIMARY_VALUE = -1;//默认主键id
    public const DB_COLUMN_EXT_ID = 'ext_id';
    public const DB_COLUMN_EXT_TYPE = 'ext_type';
    public const DB_COLUMN_EXT_DATA = 'ext_data';

    public const DB_COLUMN_PRODUCT_ID = 'product_id';
    public const DB_COLUMN_NAME = 'name';
    public const DB_COLUMN_SKU = 'sku';
    public const DB_COLUMN_SHOP_SKU = 'shop_sku';
    public const DB_COLUMN_ASIN = 'asin';
    public const DB_COLUMN_PRODUCT_STATUS = 'product_status';
    public const DB_COLUMN_IMG_URL = 'img_url';
    public const DB_COLUMN_MB_IMG_URL = 'mb_img_url';
    public const DB_COLUMN_MB_TYPE = 'mb_type';
    public const DB_COLUMN_STAR = 'star';
    public const DB_COLUMN_DES = 'des';
    public const DB_COLUMN_OPERATOR = 'operator';
    public const DB_COLUMN_UPLOAD_USER = 'upload_user';
    public const DB_COLUMN_ACTIVITY_NAME = 'activity_name';
    public const DB_COLUMN_CLICK = 'click';
    public const DB_COLUMN_LISTING_PRICE = 'listing_price';
    public const DB_COLUMN_REGULAR_PRICE = 'regular_price';
    public const DB_COLUMN_QUERY_RESULTS = 'query_results';

    public const DB_COLUMN_ACT_UNIQUE = 'act_unique';
    public const DB_COLUMN_ACT_TYPE = 'act_type';
    public const DB_COLUMN_MARK = 'mark';
    public const DB_COLUMN_START_AT = 'start_at';
    public const DB_COLUMN_END_AT = 'end_at';
    public const DB_COLUMN_IS_PARTICIPATION_AWARD = 'is_participation_award';
    public const DB_COLUMN_PRIZE_ID = 'prize_id';
    public const DB_COLUMN_QTY = 'qty';
    public const DB_COLUMN_QTY_RECEIVE = 'qty_receive';
    public const DB_COLUMN_QTY_APPLY = 'qty_apply';
    public const DB_COLUMN_SORT = 'sort';
    public const DB_COLUMN_INTERNAL_NAME = 'internal_name';
    public const DB_COLUMN_SUB_NAME = 'sub_name';
    public const DB_COLUMN_REMARKS = 'remarks';
    public const DB_COLUMN_HELP_SUM = 'help_sum';
    public const DB_COLUMN_IS_PRIZE = 'is_prize';
    public const DB_COLUMN_MAX = 'max';
    public const DB_COLUMN_WINNING_VALUE = 'winning_value';
    public const DB_COLUMN_DISCOUNT = 'discount';
    public const DB_COLUMN_USE_TYPE = 'use_type';
    public const DB_COLUMN_AMOUNT = 'amount';
    public const DB_COLUMN_START_TIME = 'satrt_time';
    public const DB_COLUMN_END_TIME = 'end_time';
    public const DB_COLUMN_AMAZON_URL = 'amazon_url';
    public const DB_COLUMN_CURRENCY_CODE = 'currency_code';
    public const DB_COLUMN_CONTENT = 'content';
    public const DB_COLUMN_ORDER_TIME = 'order_time';
    public const DB_COLUMN_ORDER_STATUS = 'order_status';
    public const DB_COLUMN_BRAND = 'brand';
    public const DB_COLUMN_ORDER_AT = 'order_at';
    public const DB_COLUMN_ORDER_ID = 'order_id';
    public const DB_COLUMN_ORDER_ITEM_ID = 'order_item_id';
    public const DB_COLUMN_AMAZON_ORDER_ID = 'amazon_order_id';
    public const DB_COLUMN_LISITING_PRICE = 'lisiting_price';
    public const DB_COLUMN_PROMOTION_DISCOUNT_AMOUNT = 'promotion_discount_amount';
    public const DB_COLUMN_PRICE = 'price';
    public const DB_COLUMN_TTEM_PRICE_AMOUNT = 'item_price_amount';
    public const DB_COLUMN_QUANTITY_ORDERED = 'quantity_ordered';
    public const DB_COLUMN_QUANTITY_SHIPPED = 'quantity_shipped';
    public const DB_COLUMN_IS_GIFT = 'is_gift';
    public const DB_COLUMN_SERIAL_NUMBER_REQUIRED = 'serial_number_required';
    public const DB_COLUMN_IS_TRANSPARENCY = 'is_transparency';
    public const DB_COLUMN_IMG = 'img';
    public const DB_COLUMN_PURCHASE_DATE_ORIGIN = 'purchase_date_origin';
    public const DB_COLUMN_SELLER_SKU = 'seller_sku';
    public const DB_COLUMN_COUNTRY_CODE = 'country_code';
    public const DB_COLUMN_PURCHASE_DATE = 'purchase_date';

    public const DB_COLUMN_RATE = 'rate';
    public const DB_COLUMN_RATE_AMOUNT = 'rate_amount';
    public const DB_COLUMN_IS_REPLACEMENT_ORDER = 'is_replacement_order';
    public const DB_COLUMN_IS_PREMIUM_ORDER = 'is_premium_order';
    public const DB_COLUMN_SHIPMENT_SERVICE_LEVEL_CATEGORY = 'shipment_service_level_category';
    public const DB_COLUMN_LATEST_SHIP_DATE = 'latest_ship_date';
    public const DB_COLUMN_EARLIEST_SHIP_DATE = 'earliest_ship_date';
    public const DB_COLUMN_SALES_CHANNEL = 'sales_channel';
    public const DB_COLUMN_IS_BUSINESS_ORDER = 'is_business_order';
    public const DB_COLUMN_FULFILLMENT_CHANNEL = 'fulfillment_channel';
    public const DB_COLUMN_PAYMENT_METHOD = 'payment_method';
    public const DB_COLUMN_IS_HAND = 'is_hand';
    public const DB_COLUMN_ORDER_TYPE = 'order_type';
    public const DB_COLUMN_SHIP_SERVICE_LEVEL = 'ship_service_level';
    public const DB_COLUMN_MODFIY_AT_TIME = 'modfiy_at_time';
    public const DB_COLUMN_LAST_UPDATE_DATE = 'last_update_date';
    public const DB_COLUMN_PULL_MODE = 'pull_mode';

    public const DB_COLUMN_IS_PRIME = 'is_prime';
    public const DB_COLUMN_BUYER_EMAIL = 'buyer_email';
    public const DB_COLUMN_BUYER_NAME = 'buyer_name';
    public const DB_COLUMN_SHIPPING_ADDRESS_NAME = 'shipping_address_name';
    public const DB_COLUMN_STATE_OR_REGION = 'state_or_region';
    public const DB_COLUMN_POSTAL_CODE = 'postal_code';
    public const DB_COLUMN_ADDRESS_LINE_1 = 'address_line_1';
    public const DB_COLUMN_ADDRESS_LINE_2 = 'address_line_2';
    public const DB_COLUMN_ADDRESS_LINE_3 = 'address_line_3';

    public const DB_COLUMN_PRODUCT_COUNTRY = 'product_country';
    public const DB_COLUMN_ORDER_COUNTRY = 'order_country';
    public const DB_COLUMN_REVIEW_LINK = 'review_link';
    public const DB_COLUMN_REVIEW_IMG_URL = 'review_img_url';
    public const DB_COLUMN_REVIEW_TIME = 'review_time';
    public const DB_COLUMN_STAR_AT = 'star_at';
    public const DB_COLUMN_ADD_TYPE = 'add_type';
    public const DB_COLUMN_ACTION = 'action';
    public const DB_COLUMN_REWARD_NAME = 'reward_name';

    public const DB_COLUMN_DICT_KEY = 'dict_key';
    public const DB_COLUMN_DICT_VALUE = 'dict_value';

    public const DB_COLUMN_CODE_TYPE = 'code_type';//code类型
    public const DB_COLUMN_IS_DEFAULT = 'is_default';//是否默认
    public const DB_COLUMN_PLATFORM_ORDER_ITEM_ID = 'platform_order_item_id';//平台订单item id
    public const DB_COLUMN_CONTACT_US_ID = 'contact_us_id';//联系我们id

    public const DB_COLUMN_VOTE_ID = 'vote_id';
    public const DB_COLUMN_VOTE_ITEM_ID = 'vote_item_id';
    public const DB_COLUMN_UNIQUE_STR = 'unique_str';

    public const QUEUE_CONNECTION = 'queue_connection';//消息队列  redis  连接
    public const QUEUE_CONNECTION_DEFAULT = 'default';//默认消息队列连接
    public const QUEUE_DELAY = 'delay';//消息队列延时key
    public const QUEUE_CHANNEL = 'channel';//消息channel
    public const MAX_ATTEMPTS = 'max_attempts';//job执行失败之后，最大尝试次数
    public const RETRY_MAX = 'retryMax';//重试最大次数key
    public const SLEEP_MIN = 'sleepMin';//睡眠最小时间key
    public const SLEEP_MAX = 'sleepMax';//睡眠最大时间key


    public const CONTEXT_REQUEST_DATA = 'contextRequestData';
    public const CONTEXT_USRE_INFO = 'userInfo';

    public const LIMIT = 'limit';
    public const LIMIT_MONTH = 'limit_month';
    public const LIMIT_WEEK = 'limit_week';
    public const LIMIT_DAY = 'limit_day';

    public const PARAMETER_INT_DEFAULT = 0;
    public const PARAMETER_STRING_DEFAULT = '';
    public const PARAMETER_ARRAY_DEFAULT = [];

    public const UPLOAD_FILE_KEY = 'file';
    public const FILE_URL = 'url';
    public const FILE_TITLE = 'title';
    public const FILE_FULL_PATH = 'fileFullPath';
    public const RESOURCE_TYPE = 'resourceType';

    public const WHETHER_YES_VALUE = 1;
    public const WHETHER_YES_VALUE_CN = '是';
    public const WHETHER_NO_VALUE = 0;
    public const WHETHER_NO_VALUE_CN = '否';

    public const EXPORT_DISTINCT_FIELD = 'distinctField';
    public const EXPORT_PRIMARY_KEY = 'primaryKey';
    public const EXPORT_PRIMARY_VALUE_KEY = 'primaryValueKey';
    public const ACT_ALIAS = 'act';
    public const ACT_PRODUCT_ALIAS = 'ap';
    public const LINKER = '.';

    public const PLATFORM_AMAZON = 'Amazon';
    public const PLATFORM_EBAY = 'Ebay';
    public const PLATFORM_SHOPEE = 'Shopee';
    public const PLATFORM_JOOM = 'Joom';
    public const PLATFORM_WISH = 'Wish';
    public const PLATFORM_ALIEXPRESS = 'Aliexpress';
    public const PLATFORM_SHOPIFY = 'Shopify';
    public const SHOPIFY_URL_PREFIX = 'pages';
    public const PLATFORM_LAZADA = 'Lazada';
    public const PLATFORM_WALMART = 'Walmart';
    public const PLATFORM_DARAZ = 'Daraz';
    public const PLATFORM_ZOODMALL = 'Zoodmall';
    public const PLATFORM_JDGLOBALSALES = 'Jdglobalsales';
    public const PLATFORM_JUMIA = 'Jumia';

    public const REQUEST_MARK = 'request_mark';
    public const ORDER_STATUS_DEFAULT = -1;
    public const ORDER_STATUS_MATCHING = 'Matching';//-1:Matching 0:Pending 1:Shipped 2:Canceled 3:Failure
    public const ORDER_STATUS_PENDING = 'Pending';
    public const ORDER_STATUS_SHIPPED = 'Shipped';
    public const ORDER_STATUS_CANCELED = 'Canceled';
    public const ORDER_STATUS_FAILURE = 'Failure';
    public const ORDER_STATUS_MATCHING_INT = -1;
    public const ORDER_STATUS_PENDING_INT = 0;
    public const ORDER_STATUS_SHIPPED_INT = 1;
    public const ORDER_STATUS_CANCELED_INT = 2;
    public const ORDER_STATUS_FAILURE_INT = 3;

    public const AUDIT_STATUS = 'audit_status';
    public const WARRANTY_AT = 'warranty_at';
    public const BUSINESS_TYPE = 'business_type';
    public const PRODUCT_TYPE = 'product_type';
    public const ORDER_DESC = 'desc';
    public const ORDER_BY = 'orderby';
    public const ORDER = 'order';

    public const QUERY = 'query';

    public const EXCEPTION_CODE = 'exception_code';
    public const EXCEPTION_MSG = 'message';

    public const WARRANTY_DATE = 'warranty_date';//订单延保时间
    public const WARRANTY_DES = 'warranty_des';//订单延保描述

    public const AVATAR = 'avatar';//头像
    public const COUNTDOWN = 'countdown';//活动倒计时
    public const END_DATE = 'end_date';//活动结束时间
    public const START_DATE = 'start_date';//活动开始时间
    public const REVIEW_STATUS = 'review_status';//审核状态
    public const EXPIRE_TIME = 'expire_time';//到期时间
    public const ACTIVITY_WINNING_ID = 'activity_winning_id';//申请id 或者 中奖id
    public const SOCIAL_MEDIA = 'social_media';//社媒
    public const IP_LIMIT_KEY = 'ip_limit';//IP限制字段key
    public const SIGNUP_KEY = 'signup';//注册key
    public const ACTION_INVITE = 'invite';//用户行为:邀请
    public const REWARD_STATUS = 'reward_status';//礼品状态
    public const ORDER_PLATFORM = 'order_platform';
    public const ACTION_ACTIVATE = 'activate';//用户行为:激活
    public const FREQUENCY = 'frequency';//用户引导:1 未引导 2+n 已引导
    public const APP_ENV = 'app_env';//app 环境
    public const LINE_ITEMS = 'line_items';//shopify order items
    public const USERNAME = 'username';//用户名
    public const CLIENT_ACCESS_URL = 'client_access_url';//用户访问页面地址
    public const REVIEW_CREDIT = 'review_credit';//review 积分
    public const REWARD_STATUS_NO = 'reward_status_no';//最近asin状态变更标识
    public const DEL_ASIN = 'del_asin';//删除asin

    public const ORDER_BIND = 'order_bind';
    public const CREATE = 'create';
    public const DB_COLUMN_TRANSACTION_ID = 'transaction_id'; //交易id
    public const DB_COLUMN_PROCESSED_AT = 'processed_at'; //处理时间
    public const DB_COLUMN_NOTE = 'note'; //备注
    public const DB_COLUMN_ITEM_ID = 'item_id'; //备注
    public const DB_COLUMN_ADDRESS_TYPE = 'address_type'; //地址类型
    public const DB_COLUMN_FULFILLMENT_ID = 'fulfillment_id'; //物流id
    public const DB_COLUMN_REFUND_ID = 'refund_id'; //退款id
    public const DB_COLUMN_REFUND_ITEM_ID = 'refund_item_id'; //退款item_id
    public const DB_COLUMN_TOTAL_TAX = 'total_tax'; //税
    public const DB_COLUMN_CURRENCY = 'currency'; //货币
    public const DB_COLUMN_PHONE = 'phone'; //电话
    public const DB_COLUMN_LOCATION_ID = 'location_id'; //位置id
    public const DB_COLUMN_FULFILLMENT_STATUS = 'fulfillment_status'; //物流状态
    public const DB_COLUMN_GATEWAY = 'gateway'; //支付网关
    public const DB_COLUMN_TEST = 'test'; //是否测试
    public const DB_COLUMN_FULFILLMENT_SERVICE = 'fulfillment_service';
    public const DB_COLUMN_QUANTITY = 'quantity'; //数量
    public const DB_COLUMN_REQUIRES_SHIPPING = 'requires_shipping';
    public const DB_COLUMN_ADMIN_GRAPHQL_API_ID = 'admin_graphql_api_id';
    public const DB_COLUMN_ADDRESS1 = 'address1'; //地址
    public const DB_COLUMN_ADDRESS2 = 'address2'; //可选地址
    public const DB_COLUMN_ZIP = 'zip'; //邮编
    public const DB_COLUMN_PROVINCE = 'province';
    public const DB_COLUMN_COMPANY = 'company'; //公司
    public const DB_COLUMN_LATITUDE = 'latitude'; //
    public const DB_COLUMN_LONGITUDE = 'longitude';
    public const DB_COLUMN_PROVINCE_CODE = 'province_code';
    public const DB_COLUMN_TRACKING_NUMBERS = 'tracking_numbers';
    public const DB_COLUMN_TRACKING_URLS = 'tracking_urls';
    public const DB_COLUMN_RECEIPT = 'receipt';
    public const DB_COLUMN_TOTAL_PRICE = 'total_price'; //总金额
    public const DB_COLUMN_PRESENTMENT_CURRENCY = 'presentment_currency';

    public const REQUEST_METHOD = 'requestMethod'; //响应数据
    public const REQUEST_URI = 'requestUri'; //响应数据
    public const REQUEST_HEADERS = 'requestHeaders'; //响应数据
    public const REQUEST_BODY = 'requestBody'; //响应数据

    public const RESPONSE_BODY = 'responseBody'; //响应数据
    public const RESPONSE_PROTOCOL_VERSION = 'responseProtocolVersion'; //协议版本
    public const RESPONSE_STATUS_CODE = 'responseStatusCode'; //响应状态码
    public const RESPONSE_REASON_PHRASE = 'responseReasonPhrase'; //响应状态码描述 OK
    public const RESPONSE_HEADERS = 'responseHeaders'; //响应头
    public const TRANSFER_TIME = 'TransferTime'; //响应时间
    public const TRANSFER_STATS = 'transferStats'; //响应状态


    public const IS_HAS_APPLY_INFO = 'is_has_apply_info';//是否提交了申请资料
    public const HAS_ONE = 'hasOne';//关联关系 一对一
    public const REVIEW_AT = 'review_at';//审核时间
    public const ACT_ID = 'actId';//审核时间
    public const BANNER_NAME = 'banner_name';//banner_name
    public const CATEGORY_ID = 'category_id';//category_id
    public const IN_STOCK = 'in_stock';//in_stock
    public const OUT_STOCK = 'out_stock';//out_stock
    public const HELP_ACCOUNT = 'help_account';//help_account
    public const PREFIX = 'prefix';//prefix
    public const AMAZON_HOST = 'amazon_host';//amazon_host
    public const DB_EXECUTION_PLAN_CONNECTION = '{connection}';
    public const DB_EXECUTION_PLAN_OR = '{or}';
    public const DB_EXECUTION_PLAN_DATATYPE_STRING = 'string';
    public const DB_EXECUTION_PLAN_ORDER_BY = 'orderBy';
    public const DB_EXECUTION_PLAN_CUSTOMIZE_WHERE = '{customizeWhere}';
    public const DB_COLUMN_DISCOUNT_PRICE = 'discount_price';
    public const DB_COLUMN_EXTINFO = 'extinfo';
    public const ACTIVITY_COUPON = 'activity_coupon';
    public const DB_EXECUTION_PLAN_GROUP_COMMON = 'common';
    public const DB_COLUMN_PRIZE_ITEM_ID = 'prize_item_id';
    public const LOTTERY_NUM = 'lotteryNum';
    public const LOTTERY_TOTAL = 'lotteryTotal';
    public const ACT_TOTAL = 'actTotal';
    public const CLICK_SHARE = 'click_share';
    public const SOCIAL_MEDIA_URL = 'social_media_url';//社媒url
    public const CLICK_VIP_CLUB = 'click_vip_club';
    public const DB_COLUMN_APPLY_ID = 'apply_id';

    public const SUBJECT = 'subject';
    public const DB_COLUMN_COUNTRY_NAME = 'country_name';
    public const DB_COLUMN_RECEIVE = 'receive';
    public const COUPON = 'coupon';
    public const ENV_PRODUCTION = 'production';
    public const DB_COLUMN_CREDIT = 'credit';
    public const START_TIME = 'start_time';
    public const DB_COLUMN_REMARK = 'remark';
    public const PLATFORM_SERVICE_SHOPIFY = 'Shopify';
    public const DB_COLUMN_EDIT_AT = 'edit_at';
    public const DB_COLUMN_PROFILE_URL = 'profile_url';
    public const DB_COLUMN_INTERESTS = 'interests';
    public const DB_COLUMN_IS_ORDER = 'isorder';
    public const CUSTOMER = 'customer';
    public const CUSTOMER_ID = 'customerId';
    public const DB_EXECUTION_PLAN_DATATYPE_DATETIME = 'datetime';
    public const RESPONSE_DATA = 'responseData';
    public const SUCCESS_COUNT = 'success_count';
    public const EXISTS_COUNT = 'exists_count';
    public const FAIL_COUNT = 'fail_count';
    public const STORE_DICT_TYPE = 'storeDictType';
    public const ENCRYPTION = 'encryption';
    public const LEVEL_ERROR = 'error';
    public const TO_EMAIL = 'to_email';
    public const STORE_DICT_TYPE_EMAIL_COUPON = 'email_coupon';
    public const ADDRESS_HOME = 'address_home';
    public const DB_COLUMN_INTEREST = 'interest';
    public const DB_COLUMN_TOTAL_CREDIT = 'total_credit';
    public const DB_COLUMN_EXP = 'exp';
    public const DB_COLUMN_VIP = 'vip';
    public const DB_COLUMN_IS_ACTIVATE = 'isactivate';
    public const DB_COLUMN_FROM_EMAIL = 'from_email';
    public const DB_COLUMN_ROW_STATUS = 'row_status';
    public const LOG_TYPE_EMAIL_DEBUG = 'email_debug';
    public const DB_COLUMN_TOPIC = 'topic';
    public const SEND_NUMS = 'send_nums';
    public const APP = 'app';
    public const EXPORT_PATH = 'export_path';
    public const RESPONSE_CACHE = 'cache';
    public const RESPONSE_COUNT = 'count';
    public const DEFAULT_WARRANTY_DATE = '2 years';//默认订单延保时间
    public const DB_COLUMN_PULL_NUM = 'pull_num';
    public const CUSTOMER_ORDER = 'customer_order';
    public const RESPONSE_WARRANTY = 'warranty';
    public const CONFIG_KEY_WARRANTY_DATE_FORMAT = 'warranty_date_format';
    public const IKICH_WARRANTY_DATE = '1-Year Extended';
    public const DB_COLUMN_PLATFORM_ORDER_ID = 'platform_order_id';
    public const DB_COLUMN_PLATFORM_CUSTOMER_ID = 'platform_customer_id';
    public const DB_COLUMN_PLATFORM_CLOSED_AT = 'platform_closed_at';
    public const DB_COLUMN_PLATFORM_CANCELLED_AT = 'platform_cancelled_at';
    public const DB_COLUMN_PLATFORM_CANCELLED_REASON = 'platform_cancel_reason';

    public const ACT_FORM_SLOT_MACHINE = 'slot_machine';
    public const WINNING_LOGS = 'winning_logs';
    public const SCORE = 'score';
    public const TOTAL_SCORE = 'total_score';

    public const BUSINESS_TYPE_ORDER = 'order';//订单
    public const BUSINESS_TYPE_FULFILLMENT = 'fulfillment';//物流
    public const BUSINESS_TYPE_REFUND = 'refund';//退款
    public const BUSINESS_TYPE_TRANSACTION = 'transaction';//交易

    public const DB_COLUMN_UNIQUE_ID = 'unique_id';//唯一id
    public const DB_COLUMN_ORDER_UNIQUE_ID = 'order_unique_id';//订单唯一id
    public const DB_COLUMN_ORDER_ITEM_UNIQUE_ID = 'order_item_unique_id';//订单 item 唯一id
    public const DB_COLUMN_FULFILLMENT_UNIQUE_ID = 'fulfillment_unique_id';//物流唯一id
    public const DB_COLUMN_REFUND_UNIQUE_ID = 'refund_unique_id';//退款唯一id
    public const DB_COLUMN_PRODUCT_UNIQUE_ID = 'product_unique_id';//产品唯一id
    public const DB_COLUMN_PRODUCT_VARIANT_UNIQUE_ID = 'product_variant_unique_id';//产品变种唯一id
    public const DB_COLUMN_PRODUCT_IMAGE_UNIQUE_ID = 'product_image_unique_id';//产品图片唯一id

    public const CUSTOMERS = 'customers';
    public const PRODUCTS = 'products';
    public const CUSTOMER_SOUTCE = 'customer_source';
    public const CURRENCY_SYMBOL = 'currency_symbol'; //货币符号


    public const FIELD = 'field';
    public const ORDER_REVIEW = 'order_review'; //

    public const DICT = 'dict'; //
    public const DICT_STORE = 'dictStore'; //

    public const HOLIFE_WARRANTY_DATE = '1-year Extended';
    public const ACTIVITY_CONFIG_TYPE = 'activityConfigType';

    public const PLATFORM_SERVICE_AMAZON = 'Amazon';
    public const DRIVER = 'driver';
    public const ACTION_TYPE = 'action_type';
    public const SUB_TYPE = 'sub_type';
    public const CLIENT_ACCESS_API_URI = 'client_access_api_uri';
    public const CLIENT_DATA = 'clientData';
    public const REQUEST_HEADER_DATA = 'headerData';

    public const DEVICE = 'device';//设备信息
    public const DEVICE_TYPE = 'device_type';// 设备类型 1:手机 2：平板 3：桌面
    public const PLATFORM_VERSION = 'platform_version';//系统版本
    public const BROWSER = 'browser';// 浏览器信息  (Chrome, IE, Safari, Firefox, ...)
    public const BROWSER_VERSION = 'browser_version';// 浏览器版本
    public const LANGUAGES = 'languages';// 语言 ['nl-nl', 'nl', 'en-us', 'en']
    public const IS_ROBOT = 'is_robot';//是否是机器人

    public const TOKEN = 'token';//token

    public const VARIANT_ID = 'variant_id';
    public const STORE_PRODUCT_ID = 'store_product_id';

    public const OWNER_RESOURCE = 'owner_resource';
    public const NAME_SPACE = 'namespace';
    public const OP_ACTION = 'op_action';
    public const METAFIELDS = 'metafields';
    public const OWNER_ID = 'owner_id';
    public const DESCRIPTION = 'description';
    public const VALUE_TYPE = 'value_type';
    public const METAFIELD_ID = 'metafield_id';
    public const EXCHANGED_NUMS = 'exchanged_nums';
    public const SORTS = 'sorts';
    public const VALID = 'valid';
    public const REWARD = 'reward';

    public const RELATED_DATA = 'relatedData';//关联数据
    public const SERIAL_HANDLE = 'serialHandle';//串行执行
    public const DB_COLUMN_STORE_DICT_KEY = 'conf_key';
    public const DB_COLUMN_STORE_DICT_VALUE = 'conf_value';
    public const ACTION_FOLLOW = 'follow';//用户行为:关注
    public const ACTION_LOGIN = 'login';//用户行为:登录
    public const REGISTER_RESPONSE = 'registerResponse';//注册响应数据
    public const ACTIVATE_EMAIL_HANDLE = 'activateEmailHandle';//激活邮件响应数据
    public const ORDER_DATA = 'orderData';//订单id
    public const REGISTERED = 'registered';//注册key
    public const IS_IP_LIMIT_WHITE_LIST = 'isIpLimitWhitelist';//是否是白名单
    public const REGISTERED_IP_LIMIT = 'registeredIpLimit';//注册时同一个ip限制注册的账号个数
    public const POINT_STORE_NAME_SPACE = 'point_store';//积分商城命名空间

    public const DB_COLUMN_USE_CHANNEL = 'use_channel';//使用场景 亚马逊 官网
    public const DB_COLUMN_PRODUCT_CODE = 'product_code';//产品 折扣码
    public const DB_COLUMN_PRODUCT_URL = 'product_url';//产品 链接

    public const DB_COLUMN_REVIEWER = 'reviewer';//审核人

    public const PLATFORM_SERVICE_LOCALHOST = 'Localhost';
    public const DB_COLUMN_CREDIT_LOG_ID = 'credit_log_id';//积分流水id
    public const PLATFORM_SERVICE_PATOZON = 'Patozon';

    public const PROCESS_PLATFORM = 'Hhxsv5';//进程平台
    public const PROCESS_PLATFORM_ILLUMINATE = 'Illuminate';//默认进程平台
    public const TASK_PLATFORM = 'Hhxsv5';//任务平台
    public const TASK_PLATFORM_ILLUMINATE = 'Illuminate';//默认任务平台

    public const DB_COLUMN_INVITE_CODE_TYPE = 'invite_code_type'; //邀请码类型

    public const PRODUCT_NAME = 'product_name';
    public const ONE_CATEGORY_NAME = 'one_category_name';
    public const TWO_CATEGORY_NAME = 'two_category_name';
    public const THREE_CATEGORY_NAME = 'three_category_name';
    public const FILE_NAME = 'file_name';
    public const NEW_FILE_URL = 'file_url';

    public const URL = 'url';
    public const CONNECTION = 'connection';
    public const NOT_EXIST = 'not-exist-!@#$%^&*()_+';
    public const ACTION_TOKEN = 'action_token';
    public const EXPIRY_TIME = 'expiry_time';
    public const CONTEXT_ERROR = 600;
    public const CONTEXT_TASK_DATA = 'task.data';
    public const HOST = 'host';
    public const ACCESS_TOKEN = 'access_token';
    public const SERVER_CALLBACK = 'serverCallback';

    public const QUEUE_AMAZON = 'queue_amazon';//亚马逊消息队列
    public const QUEUE_AMAZON_CREATE_REPORT = 'queue_amazon_create_report';//亚马逊消息队列
    public const QUEUE_AMAZON_GET_REPORT = 'queue_amazon_get_report';//亚马逊消息队列
    public const QUEUE_AMAZON_GET_REPORT_DOCUMENT = 'queue_amazon_get_report_document';//亚马逊消息队列
    public const QUEUE_AMAZON_READ_REPORT_FILE = 'queue_amazon_read_report_file';//亚马逊消息队列
    public const QUEUE_AMAZON_GET_PARENT_ASIN = 'queue_amazon_get_parent_asin';//亚马逊消息队列
    public const QUEUE_AMAZON_HANDLE_BUSINESS_DATA = 'queue_amazon_handle_business_data';//亚马逊平台组装业务数据消息队列
    public const QUEUE_AMAZON_UPDATE_CRON_PRODUCTS = 'queue_amazon_update_cron_products';//亚马逊消息队列
    public const QUEUE_AMAZON_GET_PRICE = 'queue_amazon_get_price';//亚马逊消息队列

    public const I_CACHE_CONNECTION_POOL_DEFAULT = 'i_cache';//默认缓存池
    public const CACHE_CONNECTION_POOL_TASK = 'cache_connection_pool_task';//Walmart消息队列
    public const CACHE_CREATE_TABLE_MARKER = 'create_table_marker';//创建表的缓存标记

    public const REQUEST_RETURN_STATUS = 'is_success';

    public const MIN_CONNECTIONS = 'min_connections';
    public const MAX_CONNECTIONS = 'max_connections';
    public const CONNECT_TIMEOUT = 'connect_timeout';
    public const WAIT_TIMEOUT = 'wait_timeout';
    public const HEARTBEAT = 'heartbeat';
    public const MAX_IDLE_TIME = 'max_idle_time';

    public const OPERATION_TIMED_OUT = 'Operation timed out';

    public const QUEUE = 'queue';//消息队列
    public const POOL_NAME = 'poolName';//连接池

    //Shopee
    public const QUEUE_SHOPEE = 'queue_shopee';//shopee消息队列
    public const QUEUE_SHOPEE_BASE = 'queue_shopee_base';//shopee平台listing基础消息队列
    public const QUEUE_SHOPEE_MODEL = 'queue_shopee_model';//shopee平台listing变体消息队列
    public const QUEUE_SHOPEE_DISCOUNT = 'queue_shopee_discount';//shopee平台listing折扣消息队列
    public const QUEUE_SHOPEE_SALES = 'queue_shopee_sales';//shopee平台listing销量消息队列
    public const QUEUE_SHOPEE_GLOBAL = 'queue_shopee_global';//shopee平台listing全球产品主体对应关系消息队列
    public const QUEUE_SHOPEE_GLOBAL_MODEL = 'queue_shopee_global_model';//shopee平台listing全球产品变体对应关系消息队列
    public const QUEUE_SHOPEE_TASK = 'queue_shopee_task';//shopee平台listing主任务消息队列
    public const QUEUE_SHOPEE_SKU_STATUS = 'queue_shopee_sku_status';//shopee平台listing主任务消息队列
    public const QUEUE_SHOPEE_HANDLE_BUSINESS_DATA = 'queue_shopee_handle_business_data';//shopee平台组装业务数据消息队列

    //Wish
    public const QUEUE_WISH = 'queue_wish';//Wish消息队列
    public const QUEUE_WISH_REPORT_STATUS = 'queue_wish_report_status';     //Wish消息队列      获取报告状态
    public const QUEUE_WISH_READ_REPORT = 'queue_wish_read_report';         //Wish消息队列      读取报告
    public const QUEUE_WISH_DOWNLOAD_REPORT = 'queue_wish_download_report'; //Wish消息队列      下载报告
    public const QUEUE_WISH_DOWNLOAD_REPORT_TIMING = 'queue_wish_download_report_timing'; //Wish消息队列      下载报告计时
    public const QUEUE_WISH_REQUEST_REPORT = 'queue_wish_request_report';   //Wish消息队列      请求平台获取报告
    public const QUEUE_WISH_TASK_DETAILS = 'queue_wish_task_details';       //Wish消息队列      添加item信息到子任务表
    public const QUEUE_WISH_BUSINESS_DATA = 'queue_wish_business_data';     //Wish消息队列      处理业务数据
    public const QUEUE_WISH_SAVE_INFO = 'queue_wish_save_info';             //Wish消息队列      保存item信息到业务表
    public const QUEUE_WISH_SUPPLEMENT_DATA = 'queue_wish_supplement_data'; //Wish消息队列      将失败的账号数据保存上一次拉取的
    public const QUEUE_WISH_TASK_RETRY = 'queue_wish_task_retry';           //Wish消息队列      数据检测队列

    //Aliexpress
    public const QUEUE_ALIEXPRESS = 'queue_aliexpress';//Aliexpress消息队列
    public const QUEUE_ALIEXPRESS_GET_INFO = 'queue_aliexpress_get_info';//Aliexpress获取产品详情消息队列
    public const QUEUE_ALIEXPRESS_GET_SOLD = 'queue_aliexpress_get_sold';//Aliexpress获取产品销量消息队列
    public const QUEUE_ALIEXPRESS_SAVE_DATA = 'queue_aliexpress_save_data';//Aliexpress保存到扩展信息表消息队列
    public const QUEUE_ALIEXPRESS_ADD_EXTEND = 'queue_aliexpress_add_extend';//Aliexpress添加到获取扩展表信息，保存到业务表队列消息队列
    public const QUEUE_ALIEXPRESS_SAVE_INFO = 'queue_aliexpress_save_info';//Aliexpress获取扩展表数据保存业务表队列消息队列
    public const QUEUE_ALIEXPRESS_BUSINESS_DATA = 'queue_aliexpress_business_data';//Aliexpress处理账号拉取失败的数据

    //Joom
    public const QUEUE_JOOM = 'queue_joom';//Joom消息队列
    public const QUEUE_JOOM_TASK = 'queue_joom_task';//Joom主任务消息队列
    public const QUEUE_JOOM_SUBJECT = 'queue_joom_subject';//Joom主体消息队列
    public const QUEUE_JOOM_VARIANT = 'queue_joom_variant';//Joom变体消息队列
    public const QUEUE_JOOM_PUBLISH = 'queue_joom_publish';//Joom获取刊登信息消息队列
    public const QUEUE_JOOM_CRON = 'queue_joom_cron';//Joom更新刊登信息消息队列
    public const QUEUE_JOOM_HANDLE_BUSINESS_DATA = 'queue_joom_handle_business_data';//Joom平台组装业务数据消息队列

    //Lazada
    public const QUEUE_LAZADA = 'queue_lazada';//Lazada平台消息队列
    public const QUEUE_LAZADA_TASK_DISPATCH = 'queue_lazada_task_dispatch'; //任务调度队列
    public const QUEUE_LAZADA_TASK_QC = 'queue_lazada_task_qc'; //qc数据请求队列
    public const QUEUE_LAZADA_HANDLE_BUSINESS_DATA = 'queue_lazada_handle_business_data';//Lazada平台组装业务数据消息队列

    //Walmart
    public const QUEUE_WALMART = 'queue_walmart';                                   //Walmart主消息队列
    public const QUEUE_WALMART_GET_REPORT = 'queue_walmart_get_report';             //Walmart获取报告结果
    public const QUEUE_WALMART_DOWN_REPORT = 'queue_walmart_down_report';           //Walmart下载报告
    public const QUEUE_WALMART_ANALYSIS_REPORT = 'queue_walmart_analysis_report';   //Walmart解析报告队列
    public const QUEUE_WALMART_OMISSION_SKU = 'queue_walmart_omission_sku';          //Walmart缺漏SKU处理消息队列
    public const QUEUE_WALMART_HANDLE_BUSINESS_DATA = 'queue_walmart_handle_business_data';       //Walmart平台组装业务数据消息队列

    //Ebay
    public const QUEUE_EBAY = 'queue_ebay';//Ebay消息队列
    public const QUEUE_EBAY_ITEM_DETAIL = 'queue_ebay_item_detail';//Ebay详情消息队列
    public const QUEUE_EBAY_HANDLE_BUSINESS_DATA = 'queue_ebay_handle_business_data';//ebay平台组装业务数据消息队列
    public const QUEUE_EBAY_PRODUCTS = 'queue_ebay_products';//ebay平台listing基础消息队列
    public const QUEUE_EBAY_VAT = 'queue_ebay_vat';//ebay平台listing vat 消息队列
    public const QUEUE_EBAY_SUBTITLE = 'queue_ebay_subtitle';//ebay平台listing subtitle 消息队列
    public const QUEUE_EBAY_CATEGORY = 'queue_ebay_category';//ebay平台listing category 消息队列
    public const QUEUE_EBAY_DESCRIPTION = 'queue_ebay_description';//ebay平台listing description消息队列
    public const QUEUE_EBAY_SHIPPING = 'queue_ebay_shipping';//ebay平台listing shipping 消息队列
    public const QUEUE_EBAY_SHIPPING_GLOBAL = 'queue_ebay_shipping_global';//ebay平台listing shipping global 消息队列
    public const QUEUE_EBAY_SHIPPING_LOCATIONS = 'queue_ebay_shipping_locations';//ebay平台listing shipping locations 消息队列
    public const QUEUE_EBAY_RETURNS = 'queue_ebay_returns';//ebay平台listing returns 消息队列
    public const QUEUE_EBAY_SPECIFICS = 'queue_ebay_specifics';//ebay平台listing specifics 消息队列
    public const QUEUE_EBAY_VARIATIONS = 'queue_ebay_variations';//ebay平台listing variations 消息队列
    public const QUEUE_EBAY_VARIATIONS_SPECIFICS = 'queue_ebay_variations_specifics';//ebay平台listing variations specifics消息队列
    public const QUEUE_EBAY_VARIATIONS_PICTURES = 'queue_ebay_variations_pictures';//ebay平台listing variations pictures 消息队列
    public const QUEUE_EBAY_STATUS = 'queue_ebay_status';//ebay平台更新状态队列
    public const QUEUE_EBAY_HANDLE_SUPPLEMENT_DATA = 'queue_ebay_handle_supplement_data';//ebay平台更新状态队列
    public const QUEUE_EBAY_INCREMENT = 'queue_ebay_increment';//ebay平台增量列表队列
    public const QUEUE_EBAY_INCREMENT_ITEM_DETAIL = 'queue_ebay_increment_item_detail';//Ebay详情消息队列
    public const REDIS_EBAY = 'redis_ebay';//ebay平台redis缓存
    public const CACHE_EBAY = 'cache_ebay';//ebay缓存连接池

    //美元汇率
    public const USD_EXCHANGE_RATE = 'usd_exchange_rate';
    public const DB_COLUMN_LIST_STATUS = 'list_status';
    public const CACHE_APP = 'cache_app';

    public const EXPIRY_TIME_HOURS_24 = 86400;
    public const EXPIRY_TIME_HOURS_48 = 172800;

    //Daraz
    public const QUEUE_DARAZ = 'queue_daraz';//shopee消息队列
    public const QUEUE_DARAZ_SUBJECT = 'queue_daraz_subject';//daraz平台listing主体消息队列
    public const QUEUE_DARAZ_VARIANT = 'queue_daraz_variant';//daraz平台listing变体消息队列
    public const QUEUE_DARAZ_PUBLISH = 'queue_daraz_publish';//daraz平台listing折扣消息队列
    public const QUEUE_DARAZ_QC = 'queue_daraz_qc';//daraz平台listing主任务消息队列
    public const QUEUE_DARAZ_TASK = 'queue_daraz_task';//daraz平台listing主任务消息队列
    public const QUEUE_DARAZ_HANDLE_BUSINESS_DATA = 'queue_daraz_handle_business_data';//daraz平台组装业务数据消息队列

    //Zoomall
    public const QUEUE_ZOODMALL = 'queue_zoodmall';               //zoodmall消息队列
    public const QUEUE_ZOODMALL_BUSINESS_DATA = 'queue_zoodmall_business_data'; //zoodmall消息队列  组装数据队列
    public const QUEUE_ZOODMALL_SAVE_INFO = 'queue_zoodmall_save_info';     //zoodmall消息队列  将组装查出的子任务数据，添加到该队列去保存业务数据
    public const QUEUE_ZOODMALL_SUPPLEMENT_DATA = 'queue_zoodmall_supplement_data'; //zoodmall消息队列  补数据队列
    public const QUEUE_ZOODMALL_PAGE_TASK = 'queue_zoodmall_page_task';     //zoodmall消息队列  将查询出来的分页，加入该队列进行协程获取产品数据

    public const REDIS_DARAZ = 'redis_daraz';//daraz平台redis缓存
    public const CACHE_DARAZ = 'cache_daraz';//daraz缓存连接池

    // JdGlobalsales
    public const QUEUE_JDGLOBALSALES = 'queue_jdglobalsales';// jd 消息队列
    public const QUEUE_JDGLOBALSALES_TASK_DISPATCH = 'queue_jdglobalsales_task_dispatch'; //任务调度队列
    public const QUEUE_JDGLOBALSALES_TASK_BASEBYSKU = 'queue_jdglobalsales_task_basebysku'; //任务调度队列 获取 京东sku信息
    public const QUEUE_JDGLOBALSALES_TASK_GETSTOCK = 'queue_jdglobalsales_task_getstock'; //任务调度队列 获取 京东sku库存
    public const QUEUE_JDGLOBALSALES_TASK_GETVARIANT = 'queue_jdglobalsales_task_getvariant'; //任务调度队列 获取 京东sku变体详情信息
    public const QUEUE_JDGLOBALSALES_HANDLE_BUSINESS_DATA = 'queue_jdglobalsales_handle_business_data';// 京东平台组装业务数据消息队列

    //jumia
    public const CACHE_JUMIA = 'cache_jumia';//jumia缓存连接池
    public const QUEUE_JUMIA = 'queue_jumia';// jumia 消息队列
    public const QUEUE_JUMIA_GET_PAGE_INFO = 'queue_jumia_get_page_info';// jumia  获取分页产品数据



    public const DB_CONNECTION_APP_EBAY = self::DB_CONNECTION_PREFIX_APP . 'ebay';
    public const DB_CONNECTION_APP_JOOM = self::DB_CONNECTION_PREFIX_APP . 'joom';
    public const DB_CONNECTION_APP_SHOPEE = self::DB_CONNECTION_PREFIX_APP . 'shopee';
    public const DB_CONNECTION_APP_AMAZON = self::DB_CONNECTION_PREFIX_APP . 'amazon';

    public const DB_CONNECTION_APP_WISH = self::DB_CONNECTION_PREFIX_APP . 'wish';
    public const DB_CONNECTION_APP_ALIEXPRESS = self::DB_CONNECTION_PREFIX_APP . 'aliexpress';
    public const DB_CONNECTION_APP_LAZADA = self::DB_CONNECTION_PREFIX_APP . 'lazada';
    public const DB_CONNECTION_APP_WALMART = self::DB_CONNECTION_PREFIX_APP . 'walmart';

    public const DB_CONNECTION_APP_DARAZ = self::DB_CONNECTION_PREFIX_APP . 'daraz';
    public const DB_CONNECTION_APP_ZOODMALL = self::DB_CONNECTION_PREFIX_APP . 'zoodmall';
    public const DB_CONNECTION_APP_JDGLOBALSALES = self::DB_CONNECTION_PREFIX_APP . 'jdglobalsales';
    public const DB_CONNECTION_APP_JUMIA = self::DB_CONNECTION_PREFIX_APP . 'jumia';
    //Catch
    public const DB_CONNECTION_APP_CATCH = self::DB_CONNECTION_PREFIX_APP . 'CATCH';

    public const DB_COLUMN_FAIL_TOTAL = 'fail_total';
}
