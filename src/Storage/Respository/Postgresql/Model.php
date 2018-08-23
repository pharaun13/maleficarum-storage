<?php
/**
 * This a single entity repository for Postgresql storage layer.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository\Postgresql;

class Model implements \Maleficarum\Storage\Repository\Model {
    /* ------------------------------------ Class Traits START ----------------------------------------- */
    
    use \Maleficarum\Storage\Dependant;
    
    /* ------------------------------------ Class Traits END ------------------------------------------- */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */
    
    /**
     * @see \Maleficarum\Storage\Repository\Model.create()
     */
    public function create(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Postgresql', $model->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // fetch DB DTO object
        $data = $this->getDbDTO();

        // build the query
        $query = 'INSERT INTO "' . $this->getTable() . '" (';

        // attach column names
        $temp = [];
        foreach ($data as $el) {
            $temp[] = $el['column'];
        }
        count($temp) and $query .= '"' . implode('", "', $temp) . '"';

        // attach query transitional segment
        $query .= ') VALUES (';

        // attach parameter names
        $temp = [];
        foreach ($data as $el) {
            $temp[] = $el['param'];
        }
        count($temp) and $query .= implode(', ', $temp);

        // conclude query building
        $query .= ')';

        // attach returning
        $query .= ' RETURNING *;';

        $queryParams = [];
        foreach ($data as $el) {
            $queryParams[$el['param']] = $el['value'];
        }
        $statement = $shard->prepareStatement($query, $queryParams, true);

        $statement->execute();

        // set new model ID if possible
        $this->merge($statement->fetch());
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.read()
     */
    public function read(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.update()
     */
    public function update(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.delete()
     */
    public function delete(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        return $this;
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}