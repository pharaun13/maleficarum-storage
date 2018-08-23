<?php
/**
 * This interface MUST be implemented by all Maleficarum Storage shard connection classes.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Shard;

interface Shard {
    /**
     * Connect to the storage engine.
     * 
     * @return \Maleficarum\Storage\Shard\Shard
     */
    public function connect(): \Maleficarum\Storage\Shard\Shard;
}