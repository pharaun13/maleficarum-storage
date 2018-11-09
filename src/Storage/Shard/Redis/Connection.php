<?php
/**
 * This class represents a template for redis storage connection objects.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Shard\Redis;

class Connection implements \Maleficarum\Storage\Shard\ShardInterface {
    
    /* ------------------------------------ Class Property START --------------------------------------- */
    
    /**
     * Internal storage for redis connection
     *
     * @var \Redis
     */
    private $connection = null;

    /**
     * Internal storage for host
     *
     * @var string
     */
    private $host = null;

    /**
     * Internal storage for port
     *
     * @var string
     */
    private $port = null;

    /**
     * Internal storage for password
     *
     * @var string
     */
    private $password = null;

    /**
     * Internal storage for database number
     * 
     * @var int
     */
    private $database = null;

    /**
     * Internal storage for the connection timeout value (in seconds).
     * DEFAULT: 0 [unlimited]
     *
     * @var int
     */
    private $timeout = 0;

    /**
     * This value defines how many connection attemps will be executed when attempting to connect before giving up.
     * DEFAULT: 1
     *
     * @var int
     */
    private $attempts = 1;

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Magic methods START ---------------------------------------- */
    
    /**
     * Connection constructor.
     *
     * @param \Redis $connection
     * @param string $host
     * @param int $port
     * @param int $database
     * @param string $password
     */
    public function __construct(\Redis $connection, string $host, int $port, int $database = 0, string $password = '') {
        $this->connection = $connection;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->password = $password;
    }

    /**
     * Connection destructor.
     */
    public function __destruct() {
        if ($this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    /**
     * Forward method call to the redis object
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws \LogicException
     */
    public function __call(string $method, array $arguments) {
        $connection = $this->connection;

        if (!$connection->isConnected()) {
            throw new \LogicException(sprintf('Cannot call method before connection initialization. \%s::__call()',static::class));
        }

        if (!method_exists($connection, $method)) {
            throw new \LogicException(sprintf('Method "%s" does not exist. \%s::__call()', $method, static::class));
        }

        return call_user_func_array([$connection, $method], $arguments);
    }
    
    /* ------------------------------------ Magic methods END ------------------------------------------ */

    /* ------------------------------------ Connection methods START ----------------------------------- */
    
    /**
     * @see \Maleficarum\Storage\Shard\ShardInterface.connect()
     */
    public function connect() : \Maleficarum\Storage\Shard\ShardInterface {
        $connection = $this->connection;

        if ($connection->isConnected()) {
            return $this;
        }

        $connection_attempt_counter = 0;
        while (!$connection->isConnected() && $connection_attempt_counter < $this->attempts) {
            $connection_attempt_counter++;
            
            try {
                $connection->connect($this->host, $this->port, $this->timeout);
            } catch (\RedisException $e) {
                if ($connection_attempt_counter >= $this->attempts) {
                    throw $e;
                }
            }
        }
        
        if (!empty($this->password)) {
            $connection->auth($this->password);
        }
        $connection->select($this->database);
        
        return $this;
    }

    /**
     * @see https://github.com/phpredis/phpredis#scan
     */
    public function scan(&$cursor, $pattern = '', $count = 0) {
        if (!$this->connection->isConnected()) {
            throw new \LogicException(sprintf('Cannot call method before connection initialization. \%s::scan()',static::class));
        }

        return $this->connection->scan($cursor, $pattern, $count);
    }

    /**
     * @see https://github.com/phpredis/phpredis#hScan
     */
    public function hScan(string $key, &$cursor, $pattern = '', $count = 0) {
        if (!$this->connection->isConnected()) {
            throw new \LogicException(sprintf('Cannot call method before connection initialization. \%s::hScan()',static::class));
        }

        return $this->connection->hScan($key, $cursor, $pattern, $count);
    }

    /**
     * @see https://github.com/phpredis/phpredis#zScan
     */
    public function zScan(string $key, &$cursor, $pattern = '', $count = 0) {
        if (!$this->connection->isConnected()) {
            throw new \LogicException(sprintf('Cannot call method before connection initialization. \%s::zScan()',static::class));
        }

        return $this->connection->zScan($key, $cursor, $pattern, $count);
    }

    /**
     * @see https://github.com/phpredis/phpredis#sScan
     */
    public function sScan(string $key, &$cursor, $pattern = '', $count = 0) {
        if (!$this->connection->isConnected()) {
            throw new \LogicException(sprintf('Cannot call method before connection initialization. \%s::sScan()',static::class));
        }

        return $this->connection->sScan($key, $cursor, $pattern, $count);
    }

    /**
     * @see \Maleficarum\Storage\Shard\ShardInterface.isConnected()
     */
    public function isConnected(): bool {
        return $this->connection->isConnected();
    }
    
    /* ------------------------------------ Connection methods END ------------------------------------- */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */

    /**
     * @see \Maleficarum\Storage\Shard\ShardInterface::setConnectionTimeout()
     */
    public function setConnectionTimeout(int $timeout): \Maleficarum\Storage\Shard\ShardInterface {
        if ($timeout < 1) {
            throw new \InvalidArgumentException(sprintf('Timeout value must be greater than 0. \%s::setConnectionTimeout()',static::class));
        }

        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Shard\ShardInterface::setAttempts()
     */
    public function setConnectionAttempts(int $attempts): \Maleficarum\Storage\Shard\ShardInterface {
        if ($attempts < 1) {
            throw new \InvalidArgumentException(sprintf('Attempt count must be greater than 0. \%s::setAttempts()',static::class));
        }

        $this->attempts = $attempts;
        return $this;
    }

    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}
