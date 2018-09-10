<?php
/**
 * This interface must be implemented by all Maleficarum Storage model repositories.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository;

interface ModelInterface {
    /**
     * Persist the specified model a new storage layer entity.
     *
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\ModelInterface
     */
    public function create(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface;

    /**
     * Fill the specified model with data recovered from an existing storage layer entity.
     *
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\ModelInterface
     */
    public function read(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface;

    /**
     * Update an exiting storage layer entity using the specified model.
     *
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\ModelInterface
     */
    public function update(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface;

    /**
     * Delete an existing storage layer entity based on the specified model object.
     * 
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\ModelInterface
     */
    public function delete(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface;
    
    /**
     * Perform final data transformation before the retrieved data is merged back into the model object.
     * 
     * @param array $data
     * @return array
     */
    public function transformForRetrieval(array $data): array;
    
    /**
     * Perform final data transformation before the model data is sent to the persistence layer.
     * 
     * @param array $data
     * @return array
     */
    public function transformForPersistence(array $data): array;
    
    /**
     * Get the current shard selector that will be used when calling CRUD operations on a model.
     * 
     * @param Callable $shardSelector
     * @return \Maleficarum\Storage\Repository\ModelInterface;
     */
    public function setShardSelector(Callable $shardSelector): \Maleficarum\Storage\Repository\ModelInterface;
}