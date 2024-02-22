<?php

namespace Hyperf\Database\Connectors;

use PDO;

use Swoole\Coroutine\PostgreSQL;

use OutOfBoundsException;

class PostgresConnector extends Connector implements ConnectorInterface
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * 获取 Statement Key
     * @return string
     */
    public function getStatementKey()
    {
        return __METHOD__ . md5(uniqid(mt_rand().'', true)) . microtime(true) . mt_rand();
    }

    /**
     * 抛出异常
     * @param PostgreSQL $connection
     * @param string $msg
     * @param array $code
     */
    public function throwException(PostgreSQL $connection, string $msg = '', $code = 0)
    {
        $error = (string)$connection->error;
        if ($error) {//如果有错误，就抛出异常
            throw new OutOfBoundsException(((string)$connection->error) . ($msg ? ('===>' . $msg) : $msg), $code);
        }
    }

    protected function initConfigure($connection, $config, $query)
    {
        $key = $this->getStatementKey();

        //$connection->prepare($key,$query)->execute($key,[]);

        $prepareResult = $connection->prepare($key, $query);
        $this->throwException($connection, __CLASS__ . '::configureEncoding-----prepare----exception', 8);
        if ($prepareResult === false) {
            return;
        }

        $resource = $connection->execute($key, []);
        $this->throwException($connection, __CLASS__ . '::configureEncoding-----execute----exception', 9);

        return;
    }

    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PostgreSQL
     */
    public function connect(array $config)
    {
        // First we'll create the basic DSN and connection instance connecting to the
        // using the configuration option specified by the developer. We will also
        // set the default character set on the connections to UTF-8 by default.
        $connection = $this->createConnection(
            $this->getDsn($config), $config, $this->getOptions($config)
        );

        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        $this->configureTimezone($connection, $config);

        $this->configureSchema($connection, $config);

        // Postgres allows an application_name to be set by the user and this name is
        // used to when monitoring the application with pg_stat_activity. So we'll
        // determine if the option has been specified and run a statement if so.
        $this->configureApplicationName($connection, $config);

        return $connection;
    }

    /**
     * Create a new PostgreSQL connection instance.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return \PostgreSQL
     */
    protected function createPdoConnection($dsn, $username, $password, $options)
    {

        $pg = new PostgreSQL();
        $conn = $pg->connect($dsn);
        //$this->assertNotFalse($conn, (string) $pg->error);

        //var_dump(__METHOD__,get_class($pg),$conn);

        return $pg;

//        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('pgsql', 'default');
//
//        try {
//            $client = new PostgreSQL();
//            if (false !== $client->connect($dsn)) {
//                return $client;
//            }
//
//            $logger->error('连接 pgsql 失败: ' . $client->error, [$dsn]);
//        } catch (\Throwable $t) {
//
//            $logger->error('连接 pgsql 异常: ' . $t->getMessage(), [$dsn]);
//
//            throw $t;
//        }
//
//        return null;

        //return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Set the connection character set and collation.
     *
     * @param  \PostgreSQL  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureEncoding($connection, $config)
    {
        if (! isset($config['charset'])) {
            return;
        }

//        $query = "set names '{$config['charset']}'";
//        $this->initConfigure($connection, $config, $query);

        $key = $this->getStatementKey();
        $query = "set names '{$config['charset']}'";

        //$connection->prepare($key,$query)->execute($key,[]);

        $prepareResult = $connection->prepare($key, $query);
        $this->throwException($connection, __CLASS__.'::configureEncoding-----prepare----exception', 8);
        if ($prepareResult === false) {
            return;
        }

        $resource = $connection->execute($key, []);
        $this->throwException($connection, __CLASS__.'::configureEncoding-----execute----exception', 9);

        return;

    }

    /**
     * Set the timezone on the connection.
     *
     * @param  \PostgreSQL  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $timezone = $config['timezone'];

            //$connection->prepare('key',"set time zone '{$timezone}'")->execute('key',[]);

            $key = $this->getStatementKey();
            $query = "set time zone '{$timezone}'";
            $prepareResult = $connection->prepare($key, $query);
            $this->throwException($connection, __CLASS__.'::configureTimezone-----prepare----exception', 10);
            if ($prepareResult === false) {
                return;
            }

            $resource = $connection->execute($key, []);
            $this->throwException($connection, __CLASS__.'::configureTimezone-----execute----exception', 11);

            return;

        }
    }

    /**
     * Set the schema on the connection.
     *
     * @param  \PostgreSQL  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureSchema($connection, $config)
    {
        if (isset($config['schema'])) {
            $schema = $this->formatSchema($config['schema']);

            //$connection->prepare('key',"set search_path to {$schema}")->execute('key',[]);

            $key = $this->getStatementKey();
            $query = "set search_path to {$schema}";
            $prepareResult = $connection->prepare($key, $query);
            $this->throwException($connection, __CLASS__.'::configureSchema-----prepare----exception', 12);
            if ($prepareResult === false) {
                return;
            }

            $resource = $connection->execute($key, []);
            $this->throwException($connection, __CLASS__.'::configureSchema-----execute----exception', 13);

            return;
        }
    }

    /**
     * Format the schema for the DSN.
     *
     * @param  array|string  $schema
     * @return string
     */
    protected function formatSchema($schema)
    {
        if (is_array($schema)) {
            return '"'.implode('", "', $schema).'"';
        }

        return '"'.$schema.'"';
    }

    /**
     * Set the schema on the connection.
     *
     * @param  \PostgreSQL  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureApplicationName($connection, $config)
    {
        if (isset($config['application_name'])) {
            $applicationName = $config['application_name'];

            //$connection->prepare('key',"set application_name to '$applicationName'")->execute('key',[]);

            $key = $this->getStatementKey();
            $query = "set application_name to '$applicationName'";
            $prepareResult = $connection->prepare($key, $query);
            $this->throwException($connection, __CLASS__.'::configureApplicationName-----prepare----exception', 15);
            if ($prepareResult === false) {
                return;
            }

            $resource = $connection->execute($key, []);
            $this->throwException($connection, __CLASS__.'::configureApplicationName-----execute----exception', 16);
        }
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
        extract($config, EXTR_SKIP);

//        // pgsql 默认端口
//        $config['port'] = $config['port'] ?? 5432;
//        // 默认空密码
//        $config['password'] = $config['password'] ?? '';
//        if (!isset($config['host'], $config['database'], $config['username'])) {
//            $this->logger->error('缺少必须的 pgsql 连接参数', $config);
//            return '';
//        }
//
//        if (!is_numeric($config['port']) || !(is_string($config['host']) && is_string($config['database'])
//                && is_string($config['username']) && is_string($config['password']))
//        ) {
//            $this->logger->error('无效 pgsql 连接参数', $config);
//            return '';
//        }

        return sprintf(
            'host=%s;port=%d;dbname=%s;user=%s;password=%s',
            $host,
            $port,
            $database,
            $username,
            $password
        );

//        $host = isset($host) ? "host={$host};" : '';
//
//        $dsn = "pgsql:{$host}dbname={$database}";
//
//        // If a port was specified, we will add it to this Postgres DSN connections
//        // format. Once we have done that we are ready to return this connection
//        // string back out for usage, as this has been fully constructed here.
//        if (isset($config['port'])) {
//            $dsn .= ";port={$port}";
//        }
//
//        return $this->addSslOptions($dsn, $config);
    }

    /**
     * Add the SSL options to the DSN.
     *
     * @param  string  $dsn
     * @param  array  $config
     * @return string
     */
    protected function addSslOptions($dsn, array $config)
    {
        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (isset($config[$option])) {
                $dsn .= ";{$option}={$config[$option]}";
            }
        }

        return $dsn;
    }
}
