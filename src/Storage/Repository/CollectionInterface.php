<?php
/**
 * This interface must be implemented by all Maleficarum Storage collection repositories.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository;

interface CollectionInterface {
    /**
     * Populate the specified collection based on specified conditions.
     * 
     * @param \Maleficarum\Data\Collection\Persistable\AbstractCollection $collection
     * @param array $parameters
     * @return \Maleficarum\Storage\Repository\CollectionInterface
     */
    public function populate(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection, array $parameters = []): \Maleficarum\Storage\Repository\CollectionInterface;
    
    /**
     * Persist all items in the provided collection by creating new items.
     * 
     * @param \Maleficarum\Data\Collection\Persistable\AbstractCollection $collection
     * @return \Maleficarum\Storage\Repository\CollectionInterface
     */
    public function createAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface;
    
    /**
     * Remove all items in the provided collection from the persistence layer.
     * 
     * @param \Maleficarum\Data\Collection\Persistable\AbstractCollection $collection
     * @return \Maleficarum\Storage\Repository\CollectionInterface
     */
    public function deleteAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface;

    /**
     * Perform final data transformation before the retrieved data is merged back into the collection object.
     *
     * @param array $data
     * @return array
     */
    public function transformForRetrieval(array $data): array;

    /**
     * Perform final data transformation before the collection data is sent to the persistence layer.
     *
     * @param array $data
     * @return array
     */
    public function transformForPersistence(array $data): array;

    /**
     * Get the current shard selector that will be used when calling CRUD operations on entity data set.
     *
     * @param Callable $shardSelector
     * @return \Maleficarum\Storage\Repository\CollectionInterface;
     */
    public function setShardSelector(Callable $shardSelector): \Maleficarum\Storage\Repository\CollectionInterface;
}