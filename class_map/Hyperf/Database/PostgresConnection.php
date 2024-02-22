<?php

namespace Hyperf\Database;

use Hyperf\Stringable\Str;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\Schema\PostgresBuilder;

use Doctrine\DBAL\Driver\PDOPgSql\Driver as DoctrineDriver;
use Hyperf\Database\Query\Processors\PostgresProcessor;
use Hyperf\Database\Query\Grammars\PostgresGrammar as QueryGrammar;
use Hyperf\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;

use Closure;
use Swoole\Coroutine\PostgreSQL;
use OutOfBoundsException;

class PostgresConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Hyperf\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Hyperf\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Hyperf\Database\Schema\Grammars\PostgresGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Hyperf\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOPgSql\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }

    /**
     * Get the current PostgreSQL connection.
     *
     * @return PostgreSQL
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PostgreSQL connection used for reading.
     *
     * @return PostgreSQL
     */
    public function getReadPdo()
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if ($this->recordsModified && $this->getConfig('sticky')) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Set the PDO connection.
     *
     * @param null|\Closure|PostgreSQL $pdo
     * @return $this
     */
    public function setPdo($pdo)
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Set the PDO connection used for reading.
     *
     * @param null|\Closure|PostgreSQL $pdo
     * @return $this
     */
    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Get the PostgreSQL connection to use for a select query.
     *
     * @param bool $useReadPdo
     * @return PostgreSQL
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * 获取 Statement Key
     * @return string
     */
    public function getStatementKey()
    {
        return __METHOD__ . md5(uniqid(mt_rand().'', true)) . microtime(true) . mt_rand();
    }

    public function handleQuerySql(string $query, array $bindings = []) {

        if(empty($bindings)){
            return $query;
        }

        $search = $this->getQueryGrammar()->parameter('');
        foreach ($bindings as $key => $value) {
            $replace = '$' . ($key + 1);
            $query = Str::replaceFirst($search, (string)$replace, $query);
        }

        return $query;
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

    /**
     * Run a select statement against the database.
     */
    public function select(string $query, array $bindings = [], bool $useReadPdo = true): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $query = $this->handleQuerySql($query, $bindings);

            $connection = $this->getPdoForSelect($useReadPdo);
            $key = $this->getStatementKey();

            $prepareResult = $connection->prepare($key, $query);
            $this->throwException($connection, __CLASS__.'::select-----prepare----exception', 1);
            if ($prepareResult === false) {
                return [];
            }

            $resource = $connection->execute($key, $this->prepareBindings($bindings));
            $this->throwException($connection, __CLASS__.'::select-----execute----exception', 2);
            if (empty($resource)) {//如果查询结果为空，就直接返回空数组
                return [];
            }

            $result = $connection->fetchAll($resource);

            return $result ? $result : [];
        });
    }

    /**
     * Execute an SQL statement and return the boolean result.
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $query = $this->handleQuerySql($query, $bindings);

            $key = $this->getStatementKey();
            $connection = $this->getPdo();
            $prepareResult = $connection->prepare($key, $query);

            $this->throwException($connection, __CLASS__.'::statement-----prepare----exception', 3);

            if ($prepareResult === false) {
                return $prepareResult;
            }

            $this->recordsHaveBeenModified();

            $connection->execute($key, $this->prepareBindings($bindings));

            $this->throwException($connection, __CLASS__.'::statement-----execute----exception', 5);

            return true;
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $count = 0;
            if ($this->pretending()) {
                return $count;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $query = $this->handleQuerySql($query, $bindings);

            $key = $this->getStatementKey();
            $connection = $this->getPdo();
            $prepareResult = $connection->prepare($key, $query);
            $this->throwException($connection, __CLASS__.'::affectingStatement-----prepare----exception', 6);
            if ($prepareResult === false) {
                return $count;
            }

            $result = $connection->execute($key, $this->prepareBindings($bindings));

            $this->throwException($connection, __CLASS__.'::affectingStatement-----execute----exception', 7);

            if ($result === false) {
                return $count;
            }

            $this->recordsHaveBeenModified(
                ($count = $connection->affectedRows($result)) > 0
            );

            return $count;
        });
    }

    /**
     * Create a transaction within the database.
     */
    protected function createTransaction()
    {
        if ($this->transactions == 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->getPdo()->query('BEGIN');
            } catch (Exception $e) {
                $this->handleBeginTransactionException($e);
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->createSavepoint();
        }
    }

    /**
     * Create a save point within the database.
     */
    protected function createSavepoint()
    {
        $this->getPdo()->query(
            $this->queryGrammar->compileSavepoint('trans' . ($this->transactions + 1))
        );
    }

    /**
     * Handle an exception from a transaction beginning.
     *
     * @param \Throwable $e
     *
     * @throws \Exception
     */
    protected function handleBeginTransactionException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->reconnect();

            $this->pdo->query('BEGIN');
        } else {
            throw $e;
        }
    }

    /**
     * Commit the active database transaction.
     */
    public function commit(): void
    {
        if ($this->transactions == 1) {
            $this->getPdo()->query('COMMIT');
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }

    /**
     * Perform a rollback within the database.
     *
     * @param int $toLevel
     */
    protected function performRollBack($toLevel)
    {
        if ($toLevel == 0) {
            $this->getPdo()->query('ROLLBACK');
        } elseif ($this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->query(
                $this->queryGrammar->compileSavepointRollBack('trans' . ($toLevel + 1))
            );
        }
    }
}
