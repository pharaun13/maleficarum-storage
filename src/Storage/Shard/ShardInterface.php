<?php
/**
 * This interface MUST be implemented by all Maleficarum Storage shard connection classes.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Shard;

interface ShardInterface {
    /**
     * Connect to the storage engine.
     * 
     * @return \Maleficarum\Storage\Shard\ShardInterface
     */
    public function connect(): \Maleficarum\Storage\Shard\ShardInterface;
    
    /**
     * Determine if the current shard connection is active.
     * 
     * @return bool
     */
    public function isConnected(): bool;
    
    /**
     * Set the connection timeout value (in seconds) - this how long the shard object will wait when attempting
     * to connect to the storage engine.
     * DEFAULT: engine based
     * 
     * @param int $timeout
     *
     * @return ShardInterface
     */
    public function setConnectionTimeout(int $timeout): \Maleficarum\Storage\Shard\ShardInterface;

    /**
     * Set the number of time the shard object will attempt to connect to the shard engine.
     * DEFAULT: 1
     * 
     * @param int $attempts
     *
     * @return ShardInterface
     */
    public function setConnectionAttempts(int $attempts): \Maleficarum\Storage\Shard\ShardInterface;
}