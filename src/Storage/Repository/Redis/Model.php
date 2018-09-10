<?php
/**
 * This a single entity repository for Redis storage layer.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository\Redis;

class Model implements \Maleficarum\Storage\Repository\ModelInterface {
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
        $this->setShardSelector(function(\Maleficarum\Data\Model\Persistable\AbstractModel $model) {
            return $model->getDomainGroup();
        });
    }
    
    /* ------------------------------------ Magic methods END ------------------------------------------ */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */
    
    /**
     * @see \Maleficarum\Storage\Repository\Model.create()
     */
    public function create(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($model));
        $shard->isConnected() or $shard->connect();

        // fetch DB DTO object
        $data = $this->transformForPersistence($model->getDTO());
        
        // detect the model ID - REDIS models do not support automatic Id creation
        if (!mb_strlen((string)$model->getId())) throw new \InvalidArgumentException(sprintf('Model Id undefined - redis models do not support automatic Id creation. %s::attachShard()', static::class)); 
        
        // calculate the redis key under which this model will be stored
        $key = $model->getStorageGroup().'_'.$model->getId();
        
        // persist the model
        $shard->set($key, json_encode($data));
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.read()
     */
    public function read(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($model));
        $shard->isConnected() or $shard->connect();

        // check if an Id has been specified
        if (!mb_strlen((string)$model->getId())) throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());
        
        // calculate the redis key under which this model will be stored
        $key = $model->getStorageGroup().'_'.$model->getId();
        
        // get the data from redis
        $result = $shard->get($key);
        
        // check if something got returned from redis
        if ($result === false) throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());
        
        // attempt to decode the result
        $result = json_decode($result, true);
        
        // check if the data got decoded correctly
        if (is_null($result)) throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());
        
        // transform the result and merge it into the model
        $result = $this->transformForRetrieval($result, true);
        $model->merge($result);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.update()
     */
    public function update(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($model));
        $shard->isConnected() or $shard->connect();

        // check if an Id has been specified
        if (!mb_strlen((string)$model->getId())) throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());

        // calculate the redis key under which this model will be stored
        $key = $model->getStorageGroup().'_'.$model->getId();

        // get the data from redis - if nothing was returned update should not be used since that would not update an existing model but create a new one
        $result = $shard->get($key);

        // check if something got returned from redis
        if ($result === false) throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());

        // fetch DB DTO object
        $data = $this->transformForPersistence($model->getDTO());
        
        // persist the model
        $shard->set($key, json_encode($data));
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.delete()
     */
    public function delete(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\ModelInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Redis', ($this->shardSelector)($model));
        $shard->isConnected() or $shard->connect();

        // lack of Id inside a redis storage could lead to removing something that's not supposed to be removed but we don't want to throw exceptions when something like that is attempted  
        if (!mb_strlen((string)$model->getId())) return $this;

        // calculate the redis key under which this model will be stored
        $key = $model->getStorageGroup().'_'.$model->getId();
        $shard->del($key);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.transformForRetrieval()
     */
    public function transformForRetrieval(array $data): array {
        return $data;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.transformForPersistence()
     */
    public function transformForPersistence(array $data): array {
        return $data;
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */

    /**
     * @see \Maleficarum\Storage\Repository\ModelInterface.setShardSelector()
     */
    public function setShardSelector(Callable $shardSelector): \Maleficarum\Storage\Repository\ModelInterface {
        $this->shardSelector = $shardSelector;
        return $this;
    }

    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}