<?php
/**
 * This class provides storage shard routing functionalities.
 */
declare (strict_types=1);

namespace Maleficarum\Storage;

class Manager {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Name of the default shard route
     *
     * @var String
     */
    const DEFAULT_ROUTE = '__DEFAULT__';

    /**
     * Internal storage for route to shard mapping.
     *
     * @var array
     */
    private $routes = [];
    
    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */
    
    /**
     * Attach a new shard of the specified type to the specified route.
     *
     * @param \Maleficarum\Storage\Shard\ShardInterface $shard
     * @param string $type
     * @param string $route
     *
     * @return \Maleficarum\Storage\Manager
     */
    public function attachShard(\Maleficarum\Storage\Shard\ShardInterface $shard, string $type, string $route): \Maleficarum\Storage\Manager {
        if (!mb_strlen($type)) {
            throw new \InvalidArgumentException(sprintf('Incorrect shard type - non empty string expected. %s::attachShard()', static::class));
        }
        
        if (!mb_strlen($route)) {
            throw new \InvalidArgumentException(sprintf('Incorrect route provided - non empty string expected. %s::attachShard()', static::class));
        }

        array_key_exists($type, $this->routes) or $this->routes[$type] = [];
        $this->routes[$type][$route] = $shard;

        return $this;
    }

    /**
     * Detach an existing shard of the specified type from the specified route.
     *
     * @param string $type
     * @param string $route
     *
     * @return \Maleficarum\Storage\Manager
     */
    public function detachShard(string $type, string $route): \Maleficarum\Storage\Manager {
        if (!mb_strlen($type)) {
            throw new \InvalidArgumentException(sprintf('Incorrect shard type - non empty string expected. %s::detachShard()', static::class));
        }
        
        if (!mb_strlen($route)) {
            throw new \InvalidArgumentException(sprintf('Incorrect route provided - non empty string expected. %s::detachShard()', static::class));
        }

        if (array_key_exists($type, $this->routes)) {
            if (array_key_exists($route, $this->routes[$type]))
            unset($this->routes[$type][$route]);
        }

        return $this;
    }

    /**
     * Fetch a shard of the specified type for the specified route. If such route is not defined a default shard will be fetched.
     *
     * @param string $type
     * @param string $route
     *
     * @return \Maleficarum\Storage\Shard\ShardInterface
     */
    public function fetchShard(string $type, string $route): \Maleficarum\Storage\Shard\ShardInterface {
        if (!mb_strlen($type)) {
            throw new \InvalidArgumentException(sprintf('Incorrect shard type - non empty string expected. %s::fetchShard()', static::class));
        }
        
        if (!mb_strlen($route)) {
            throw new \InvalidArgumentException(sprintf('Incorrect route provided - non empty string expected. %s::fetchShard()', static::class));
        }

        if (array_key_exists($type, $this->routes)) {
            if (array_key_exists($route, $this->routes[$type])) {
                return $this->routes[$type][$route];
            }

            if (array_key_exists(self::DEFAULT_ROUTE, $this->routes[$type])) {
                return $this->routes[$type][self::DEFAULT_ROUTE];
            }
        }

        throw new \InvalidArgumentException(sprintf('Impossible to fetch the specified route of the specified type. %s::fetchShard()', static::class));
    }

    /**
     * Fetch an array of all shards of the specified type. (shards will be indexed by route and the default route will not be returned unless it's the only one)
     *
     * @param string $type
     *
     * @return array
     */
    public function fetchShards(string $type) {
        if (!mb_strlen($type)) {
            throw new \InvalidArgumentException(sprintf('Incorrect shard type - non empty string expected. %s::fetchShards()', static::class));
        }

        if (array_key_exists($type, $this->routes) && count($this->routes[$type])) {
            if (count($this->routes[$type]) === 1) {
                return $this->routes[$type];
            } else {
                $ret_val = $this->routes[$type];
                unset($ret_val[self::DEFAULT_ROUTE]);
                return $ret_val;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown type specified. %s::fetchShard()', static::class));
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}
