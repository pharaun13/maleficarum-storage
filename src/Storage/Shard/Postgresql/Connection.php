<?php
/**
 * This class represents a template for postgresql storage connection objects.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Shard\Postgresql;

class Connection implements \Maleficarum\Storage\Shard\ShardInterface {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Internal storage for the PDO connection.
     *
     * @var \PDO
     */
    private $connection = null;

    /**
     * Internal storage for the connections host.
     *
     * @var string
     */
    private $host = null;

    /**
     * Internal storage for the connections TCP port.
     *
     * @var int
     */
    private $port = null;

    /**
     * Internal storage for the connections database.
     *
     * @var string
     */
    private $dbname = null;

    /**
     * Internal storage for the connections username.
     *
     * @var string
     */
    private $username = null;

    /**
     * Internal storage for the connections password.
     *
     * @var string
     */
    private $password = null;

    /**
     * Internal storage for the connection timeout value (in seconds).
     * DEFAULT: 20 [seconds]
     * 
     * @var int 
     */
    private $timeout = 20;

    /**
     * This value defines how many connection attemps will be executed when attempting to connect before giving up.
     * DEFAULT: 1
     * 
     * @var int 
     */
    private $attempts = 1;
    
    /**
     * Internal storage for prepare statements so that we don't have to prepare them again.
     * 
     * @var array 
     */
    private $statements = [];
    
    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Magic methods START ---------------------------------------- */

    /**
     * Connection constructor.
     *
     * @param string $host
     * @param int    $port
     * @param string $database
     * @param string $username
     * @param string $password
     */
    public function __construct(string $host, int $port, string $database, string $username, string $password) {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $database;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Method call delegation to the wrapped PDO instance
     *
     * @param string $name
     * @param array  $args
     * @return mixed
     */
    public function __call(string $name, array $args) {
        if (is_null($this->connection)) {
            throw new \RuntimeException(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::__call()', static::class));
        }

        if (!method_exists($this->connection, $name)) {
            throw new \InvalidArgumentException(sprintf('Method %s unsupported by PDO. \%s::__call()', $name, static::class));
        }

        return call_user_func_array([$this->connection, $name], $args);
    }
    
    /* ------------------------------------ Magic methods END ------------------------------------------ */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Storage\Shard\ShardInterface::connect()
     */
    public function connect(): \Maleficarum\Storage\Shard\ShardInterface {
        if ($this->connection instanceof \PDO) {
            return $this;
        }
        
        $connection = [
            'pgsql:host='.$this->host.';port='.$this->port.';dbname='.$this->dbname,
            $this->username,
            $this->password,
            [
                \PDO::ATTR_TIMEOUT => $this->timeout,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]
        ];
        
        $connection_attempt_counter = 0;
        while (is_null($this->connection) && $connection_attempt_counter < $this->attempts) {
            $connection_attempt_counter++;
            
            try {
                $this->connection = \Maleficarum\Ioc\Container::get('PDO', $connection);
                $this->connection->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Maleficarum\Storage\Shard\Postgresql\PDO\Statement\Generic', [$this->connection]]);
            } catch (\PDOException $e) {
                if ($connection_attempt_counter >= $this->attempts) {
                    throw $e;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * @see \Maleficarum\Storage\Shard\ShardInterface::isConnected()
     */
    public function isConnected(): bool {
        return !is_null($this->connection);
    }
    
    /**
     * @param string $statement
     * @param array  $driver_options
     * @return \PDOStatement
     */
    public function prepare(string $statement, array $driver_options = []): \PDOStatement {
        if (is_null($this->connection)) {
            throw new \RuntimeException(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::__call()', static::class));
        }
        
        // attempt to retrieve statement from local cache 
        $hash = crc32($statement);
        if (array_key_exists($hash, $this->statements)) {
            return $this->statements[$hash];
        }
        
        // prepare the statement and place it in cache
        $statement = $this->connection->prepare($statement, $driver_options);
        $this->statements[$hash] = $statement;
        
        return $statement;
    }
    
    /**
     * Lock a table in specified mode.
     * 
     * @param string $table
     * @param string $mode
     * @return \Maleficarum\Storage\Shard\Postgresql\Connection
     */
    public function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE'): \Maleficarum\Storage\Shard\Postgresql\Connection {
        if (is_null($this->connection)) {
            throw new \RuntimeException(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::lockTable()', static::class));
        }

        if (!$this->inTransaction()) {
            throw new \RuntimeException(sprintf('No active transaction - cannot lock a table outside of a transaction scope. \%s::lockTable()', static::class));
        }

        $this->query('LOCK "' . $table . '" IN ' . $mode . ' MODE');

        return $this;
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */

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