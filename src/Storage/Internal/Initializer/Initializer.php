<?php
/**
 * This class carries ioc initialization functionality to used by the storage component.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Internal\Initializer;

class Initializer {
    /**
     * This will setup all IOC definitions specific to this component.
     *
     * @param array $opts
     *
     * @return string
     */
    static public function initialize(array $opts = []): string {
        // load default builder if skip not requested
        $builders = $opts['builders'] ?? [];
        is_array($builders) or $builders = [];

        if (!isset($builders['storage']['skip'])) {
            self::registerPostgresqlInitializers();
            self::registerRedisInitializers();
            self::registerManagerInitializers();
        }
        
        $shards = \Maleficarum\Ioc\Container::get('Maleficarum\Storage\Manager');
        \Maleficarum\Ioc\Container::registerShare('Maleficarum\Storage', $shards);

        return __METHOD__;
    }
    
    /**
     * Add Postgresql shard builder definitions to the IoC component.
     */
    static private function registerPostgresqlInitializers(): void {
        \Maleficarum\Ioc\Container::registerBuilder('Maleficarum\Storage\Shard\Postgresql\Connection', function ($dep, $opts) {
            // validate input params - host
            if (!array_key_exists('host', $opts) || !mb_strlen($opts['host'])) {
                throw new \InvalidArgumentException(sprintf('Host not specified correctly. %s',static::class));
            }
            
            // validate input params - port
            if (!array_key_exists('port', $opts) || !is_int($opts['port'])) {
                throw new \InvalidArgumentException(sprintf('Port not specified correctly. %s',static::class));
            }

            // validate input params - database name
            if (!array_key_exists('database', $opts) || !mb_strlen($opts['database'])) {
                throw new \InvalidArgumentException(sprintf('Database name not specified correctly. %s',static::class));
            }
            
            // validate input params - username
            if (!array_key_exists('username', $opts) || !mb_strlen($opts['username'])) {
                throw new \InvalidArgumentException(sprintf('Username not specified correctly. %s',static::class));
            }

            // validate input params - password
            if (!array_key_exists('password', $opts) || !mb_strlen($opts['password'])) {
                throw new \InvalidArgumentException(sprintf('Password not specified correctly. %s',static::class));
            }
            
            // create the shard connection object
            $connection = new \Maleficarum\Storage\Shard\Postgresql\Connection (
                $opts['host'],
                $opts['port'],
                $opts['database'],
                $opts['username'],
                $opts['password']
            );

            // set connection timeout if defined
            array_key_exists('connection_timeout', $opts) and $connection->setConnectionTimeout($opts['connection_timeout']);

            // set connection attempt count if defined
            array_key_exists('connection_attempts', $opts) and $connection->setConnectionAttempts($opts['connection_attempts']);
            
            return $connection;
        });
    }
    
    /**
     * Add Redis shard builder definitions to the IoC component.
     */
    static private function registerRedisInitializers(): void {
        \Maleficarum\Ioc\Container::registerBuilder('Maleficarum\Storage\Shard\Redis\Connection', function ($dep, $opts) {
            // validate input params - host
            if (!array_key_exists('host', $opts) || !mb_strlen($opts['host'])) {
                throw new \InvalidArgumentException(sprintf('Host not specified correctly. %s',static::class));
            }

            // validate input params - port
            if (!array_key_exists('port', $opts) || !is_int($opts['port'])) {
                throw new \InvalidArgumentException(sprintf('Port not specified correctly. %s',static::class));
            }

            // validate input params - database
            if (!array_key_exists('database', $opts) || !is_int($opts['database'])) {
                throw new \InvalidArgumentException(sprintf('Database not specified correctly. %s',static::class));
            }
            
            // create the shard connection object
            $connection = new \Maleficarum\Storage\Shard\Redis\Connection(
                \Maleficarum\Ioc\Container::get('redis'), 
                $opts['host'],
                $opts['port'],
                $opts['database'],
                array_key_exists('auth', $opts) ? $opts['auth'] : ''
            );
            
            // set connection timeout if defined
            array_key_exists('connection_timeout', $opts) and $connection->setConnectionTimeout($opts['connection_timeout']);
            
            // set connection attempt count if defined
            array_key_exists('connection_attempts', $opts) and $connection->setConnectionAttempts($opts['connection_attempts']); 
            
            return $connection;
        });
    }
    
    /**
     * Add shard manager builder definitions to the IoC component.
     */
    static private function registerManagerInitializers(): void {
        \Maleficarum\Ioc\Container::registerBuilder('Maleficarum\Storage\Manager', function ($dep, $opts) {
            $manager = new \Maleficarum\Storage\Manager();
            
            if (isset($dep['Maleficarum\Config'])) {
                $config = $dep['Maleficarum\Config'];
                
                // Postgresql shards
                if (isset($config['storage::postgresql'])) {
                    // validate shard config definitions
                    if (
                        !isset($config['storage']['postgresql_shard']) 
                        || !is_array($config['storage']['postgresql_shard'])
                        || !count($config['storage']['postgresql_shard'])
                        || !isset($config['storage']['postgresql_shard_default'])
                    ) {
                        throw new \RuntimeException(sprintf('Postgresql shards defined but routing was not properly defined. %s', static::class));
                    }
                    
                    // create and attach postgresql shards to the storage manager
                    $connections = [];
                    foreach ($config['storage']['postgresql_shard'] as $route => $shard) {
                        // sanitize config parameters
                        $params = $config['storage::postgresql'][$shard];
                        $params['port'] = (int)$params['port'];
                        isset($params['connection_timeout']) and $params['connection_timeout'] = (int)$params['connection_timeout'];
                        isset($params['connection_attempts']) and $params['connection_attempts'] = (int)$params['connection_attempts'];
                        
                        // get the connection object 
                        array_key_exists($shard, $connections) or $connections[$shard] = \Maleficarum\Ioc\Container::get('Maleficarum\Storage\Shard\Postgresql\Connection', $params);
                        $connection = $connections[$shard];
                            
                        // attach the shard
                        $manager->attachShard( $connection,'Postgresql', $route);
                        
                        // attach default route
                        if ($shard === $config['storage']['postgresql_shard_default']) $manager->attachShard($connection, 'Postgresql', '__DEFAULT__');
                    }
                }
                
                // Redis shards
                if (isset($config['storage::redis'])) {
                    // validate shard config definitions
                    if (
                        !isset($config['storage']['redis_shard'])
                        || !is_array($config['storage']['redis_shard'])
                        || !count($config['storage']['redis_shard'])
                        || !isset($config['storage']['redis_shard_default'])
                    ) {
                        throw new \RuntimeException(sprintf('Postgresql shards defined but routing was not properly defined. %s', static::class));
                    }

                    // create and attach postgresql shards to the storage manager
                    $connections = [];
                    foreach ($config['storage']['redis_shard'] as $route => $shard) {
                        // sanitize config parameters
                        $params = $config['storage::redis'][$shard];
                        $params['port'] = (int)$params['port'];
                        $params['database'] = (int)$params['database'];
                        isset($params['connection_timeout']) and $params['connection_timeout'] = (int)$params['connection_timeout'];
                        isset($params['connection_attempts']) and $params['connection_attempts'] = (int)$params['connection_attempts'];
                            
                        // get the connection object
                        array_key_exists($shard, $connections) or $connections[$shard] = \Maleficarum\Ioc\Container::get('Maleficarum\Storage\Shard\Redis\Connection', $params);
                        $connection = $connections[$shard];

                        // attach the shard
                        $manager->attachShard( $connection,'Redis', $route);

                        // attach default route
                        if ($shard === $config['storage']['redis_shard_default']) $manager->attachShard($connection, 'Redis', '__DEFAULT__');
                    }
                }
            }
            
            return $manager;
        });
    }
}
