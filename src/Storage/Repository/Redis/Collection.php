<?php
/**
 * This a multi entity repository for Redis storage layer.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository\Redis;

class Collection implements \Maleficarum\Storage\Repository\CollectionInterface {
    /* ------------------------------------ Class Traits START ----------------------------------------- */

    use \Maleficarum\Storage\DependantTrait;
    
    /* ------------------------------------ Class Traits END ------------------------------------------- */

    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * This is the definition of the shard selector function used by this repository. If set it will be
     * called to determine the shard route used for the data currently stored in the model object specified
     * for CRUD operations.
     *
     * @var \Callable
     */
    protected $shardSelector = null;
    
    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Magic methods START ---------------------------------------- */

    /**
     * Set the default shard selector implementation.
     */
    public function __construct() {
        $this->setShardSelector(function(\Maleficarum\Data\Collection\Persistable\AbstractCollection $model) {
            return $model->getDomainGroup();
        });
    }
    
    /* ------------------------------------ Magic methods END ------------------------------------------ */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.populate()
     */
    public function populate(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection, array $parameters = []): \Maleficarum\Storage\Repository\CollectionInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($collection));
        $shard->isConnected() or $shard->connect();
        
        // recover item ids - redis repositories only allow for searching based on key names
        $ids = [];
        foreach ($parameters as $key => $val) {
            if ($key !== $collection->getModelPrefix().'Id') continue;
            foreach ($val as $id) $ids[] = $collection->getStorageGroup().'_'.$id;
        }
        
        // no ids means that no fetching is needed
        if (!count($ids)) $this->respondToInvalidArgument('No valid ids were specified - collection hydration halted. %s');
        
        // fetch data
        $result = $shard->mget($ids);
        
        // decode data 
        $temp = [];
        while (count($result)) {
            $entry = array_shift($result);
            $entry = json_decode((string)$entry, true);
            is_null($entry) or $temp[] = $entry;
        }
        $result = $temp;

        // apply retrieval transformations
        $result = $this->transformForRetrieval($result);

        // transfer data to the collection object
        $collection->populate($result);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.createAll()
     */
    public function createAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($collection));
        $shard->isConnected() or $shard->connect();

        // transform data for persistence
        $data = $this->transformForPersistence($collection->toArray());
        
        // create the data structure
        $structure = [];
        foreach ($data as $element) {
            // check if element is an array
            is_array($element) or $this->respondToInvalidArgument('Only array elements can be persisted via this repository. %s');
            
            // check if element has an Id
            if (!array_key_exists($collection->getModelPrefix().'Id', $element) && mb_strlen((string)$element[$collection->getModelPrefix().'Id'])) {
                $this->respondToInvalidArgument('Invalid element specified no Id ('.$collection->getModelPrefix().'Id) provided. %s');
            }
            
            // add the element to the structure
            $structure[$collection->getStorageGroup().'_'.$element[$collection->getModelPrefix().'Id']] = json_encode($element);
        }
        
        // persist the data in the storage layer
        count($structure) and $shard->mset($structure);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.deleteAll()
     */
    public function deleteAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($collection));
        $shard->isConnected() or $shard->connect();

        // transform data for persistence
        $data = $this->transformForPersistence($collection->toArray());
        
        // extract keys to delete
        $structure = [];
        foreach ($data as $element) {
            // check if element is an array
            is_array($element) or $this->respondToInvalidArgument('Only array elements can be deleted via this repository. %s');

            // check if element has an Id
            if (!array_key_exists($collection->getModelPrefix() . 'Id', $element) && mb_strlen((string) $element[$collection->getModelPrefix() . 'Id'])) {
                $this->respondToInvalidArgument('Invalid element specified no Id (' . $collection->getModelPrefix() . 'Id) provided. %s');
            }
            
            $structure[] = $collection->getStorageGroup().'_'.$element[$collection->getModelPrefix().'Id'];
        }
        
        // remove data from the persistence layer
        count($structure) and $shard->delete($structure);
        
        return $this;
    }
    
    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.transformForRetrieval()
     */
    public function transformForRetrieval(array $data): array {
        return $data;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.Repository()
     */
    public function transformForPersistence(array $data): array {
        return $data;
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */

    /* ------------------------------------ Helper methods START --------------------------------------- */
    
    /**
     * This method is a code style helper - it will simply throw an \InvalidArgumentException
     *
     * @param string $msg
     * @return void
     */
    protected function respondToInvalidArgument(string $msg) {
        throw new \InvalidArgumentException(sprintf($msg, static::class));
    }

    /* ------------------------------------ Helper methods END ----------------------------------------- */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.setShardSelector()
     */
    public function setShardSelector(Callable $shardSelector): \Maleficarum\Storage\Repository\CollectionInterface {
        $this->shardSelector = $shardSelector;
        return $this;
    }
    
    /* ------------------------------------ Setters & Getters END -------------------------------------- */
};