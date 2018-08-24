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
        $data = $this->transformForPersistence($model->getDTO());
        $data = $this->getDbDTO($model, $data);

        // build the query
        $query = 'INSERT INTO "' . $model->getStorageGroup() . '" (';

        // attach column names
        $temp = [];
        foreach ($data as $el) $temp[] = $el['column'];
        count($temp) and $query .= '"' . implode('", "', $temp) . '"';

        // attach query transitional segment
        $query .= ') VALUES (';

        // attach parameter names
        $temp = [];
        foreach ($data as $el) $temp[] = $el['param'];
        count($temp) and $query .= implode(', ', $temp);

        // conclude query building
        $query .= ')';

        // attach returning
        $query .= ' RETURNING *;';

        // set up and execute the PDO statement
        $statement = $shard->prepare($query);

        foreach ($data as $el) $statement->bindValue($el['param'], $el['value']);

        $statement->execute();

        // set new model ID if possible
        $result = $this->transformForRetrieval($statement->fetch());
        $model->merge($result);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.read()
     */
    public function read(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Postgresql', $model->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // build the query
        $query = 'SELECT * FROM "' . $model->getStorageGroup() . '" WHERE "' . $model->getModelPrefix() . 'Id" = :id';
        
        $statement = $shard->prepare($query);
        $statement->bindValue(":id", $model->getId());

        if (!$statement->execute() || $statement->rowCount() !== 1) {
            throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());
        }

        // fetch results and merge them into this object
        $result = $this->transformForRetrieval($statement->fetch());
        $model->merge($result);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.update()
     */
    public function update(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Postgresql', $model->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // fetch DB DTO object
        $data = $this->transformForPersistence($model->getDTO());
        $data = $this->getDbDTO($model, $data);
        
        // build the query
        $query = 'UPDATE "' . $model->getStorageGroup() . '" SET ';

        // attach data definition
        $temp = [];
        foreach ($data as $el) {
            $temp[] = '"' . $el['column'] . '" = ' . $el['param'];
        }
        $query .= implode(", ", $temp) . " ";

        // conclude query building
        $query .= 'WHERE "' . $model->getModelPrefix() . 'Id" = :id RETURNING *';

        // set up and execute the PDO statement
        $statement = $shard->prepare($query);
        
        foreach ($data as $el) $statement->bindValue($el['param'], $el['value']);
        $statement->bindValue(":id", $model->getId());

        if (!$statement->execute() || $statement->rowCount() !== 1) {
            throw new \Maleficarum\Storage\Exception\Repository\EntityNotFoundException(get_class($model), (string)$model->getId());
        }

        // refresh current data with data returned from the database
        $result = $this->transformForRetrieval($statement->fetch());
        $model->merge($result);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\Model.delete()
     */
    public function delete(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Postgresql', $model->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // build the query
        $query = 'DELETE FROM "' . $model->getStorageGroup() . '" WHERE "' . $model->getModelPrefix() . 'Id" = :id';
        
        // set up and execute the PDO statement
        $statement = $shard->prepare($query);

        $statement->bindValue(":id", $model->getId());

        $statement->execute();
        
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

    /**
     * This method returns an array of properties to be used in INSERT and UPDATE CRUD operations. The format for each
     * entry is as follows:
     *
     * $entry['param'] = ':bindParamName';
     * $entry['value'] = 'Param value (as used during the bind process)';
     * $entry['column'] = 'Name of the storage column to bind against.';
     *
     * @return array
     */
    protected function getDbDTO(\Maleficarum\Data\Model\Persistable\AbstractModel $model, array $data): array {
        $result = [];
        
        // establish the DB dto object
        foreach ($data as $field_name => $field_value) {
            // entity Ids are not to be included in the db dto - these must not be updated or created manually by default
            if ($field_name === $model->getModelPrefix().'Id') continue;
            
            // properties not starting with the model prefix must not be part of the db dto 
            if (strpos($field_name, $model->getModelPrefix()) !== 0) continue;

            // properties without a getter must not be part of the db dto
            $methodName = 'get' . str_replace(' ', "", ucwords($field_name));
            if (!method_exists($model, $methodName)) {
                continue;
            }

            $result[$field_name] = ['param' => ':' . $field_name . '_token_0', 'value' => $field_value, 'column' => $field_name];
        }
        
        return $result;
    }

    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}