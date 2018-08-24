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
}