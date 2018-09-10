<?php
/**
 * This a multi entity repository for Postgresql storage layer.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository\Postgresql;

class Collection implements \Maleficarum\Storage\Repository\CollectionInterface
{
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
        // fetch the shard object
        $shard = $this->getStorage()->fetchShard('Postgresql', ($this->shardSelector)($collection));
        
        // execute parameter checks
        $this->populate_testSpecialMarkers($parameters, $shard);

        // create the DTO transfer object
        $dto = (object)['params' => [], 'data' => $parameters];

        // initialize query prepend section
        $query = $this->populate_prependSection($dto);

        // initial query definition
        $query = $this->populate_basicQuery($query, $dto, $collection->getStorageGroup());

        // attach filters
        $query = $this->populate_attachFilter($query, $dto);

        // blanket SQL statement
        $query = $this->populate_blanketSQL($query);

        // attach grouping info
        (array_key_exists('__count', $parameters) || array_key_exists('__sum', $parameters)) and $query = $this->populate_grouping($query, $dto);

        // attach sorting info
        array_key_exists('__sorting', $parameters) and $query = $this->populate_sorting($query, $dto);

        // attach subset info
        array_key_exists('__subset', $parameters) and $query = $this->populate_subset($query, $dto);

        // attach lock directive (caution - this can cause deadlocks when used incorrectly)
        array_key_exists('__lock', $parameters) and $query = $this->populate_lock($query, $dto);
        
        // establish the connection if necessary
        $shard->isConnected() or $shard->connect();
        
        // fetch data from the persistence layer
        $statement = $shard->prepare($query);
        foreach ($dto->params as $param => $value) $statement->bindValue($param, $value);
        $statement->execute();
        
        $result = $statement->fetchAll();
        
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
        // fetch the shard object
        $shard = $this->getStorage()->fetchShard('Postgresql', ($this->shardSelector)($collection));
        
        // transform data for persistence
        $data = $this->transformForPersistence($collection->toArray());
        
        // setup containers
        $sets = [];
        $params = [];
        
        // establish data containers
        foreach ($data as $key => &$val) {
            // remove id column if present
            if (array_key_exists($collection->getModelPrefix().'Id', $val)) unset($val[$collection->getModelPrefix().'Id']);
            
            $result = [];
            array_walk($val, function ($local_value, $local_key) use (&$result, $key) {
                $result[':' . $local_key . '_token_' . $key] = $local_value;
            });

            // append sets
            $sets[] = "(" . implode(', ', array_keys($result)) . ")";

            // append bind params
            $params = array_merge($params, $result);
        }
        
        // generate SQL query
        $sql = 'INSERT INTO "' . $collection->getStorageGroup() . '" ("' . implode('", "', array_keys($data[0])) . '") VALUES ';
        $sql .= implode(', ', $sets);
        $sql .= ' RETURNING *;';

        // establish the connection if necessary
        $shard->isConnected() or $shard->connect();
        
        // send data to the persistence layer
        $statement = $shard->prepare($sql);
        foreach ($params as $k => $v) $statement->bindValue($k, $v);
        $statement->execute();
        
        $result = $statement->fetchAll();

        // apply retrieval transformations
        $result = $this->transformForRetrieval($result);

        // transfer data to the collection object
        $collection->populate($result);
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.deleteAll()
     */
    public function deleteAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface {
        // fetch the shard object
        $shard = $this->getStorage()->fetchShard('Postgresql', ($this->shardSelector)($collection));
        
        // extract entity ids
        $ids = [];
        foreach ($collection->toArray() as $key => $col_element) {
            isset($col_element[$collection->getModelPrefix().'Id']) and $ids[':_id_token_'.$key] = $col_element[$collection->getModelPrefix().'Id']; 
        }
        
        // build the sql query
        $temp = [];
        $sql = 'DELETE FROM "' . $collection->getStorageGroup() . '" WHERE "'.$collection->getModelPrefix().'Id" IN (';
        foreach ($ids as $key => $val) $temp[] = $key;
        $sql .= implode(', ', $temp).')';
        
        // remove data from the persistence layer
        $statement = $shard->prepare($sql);
        foreach ($ids as $k => $v) $statement->bindValue($k, $v);
        $statement->execute();
        
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

    /**
     * Fetch a list of columns that can be used for collection sorting. Empty array means that no sorting is available for this collection.
     * 
     * @return array
     */
    protected function getSortColumns(): array {
        return [];
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */

    /* ------------------------------------ Populate Methods START ------------------------------------- */

    /**
     * Validate hydration special markers.
     * 
     * @param array                                            $data
     * @param \Maleficarum\Storage\Shard\Postgresql\Connection $shard
     * @return \Maleficarum\Storage\Repository\Postgresql\Collection
     */
    protected function populate_testSpecialMarkers(array $data, \Maleficarum\Storage\Shard\Postgresql\Connection $shard) {
        // LOCK
        if (array_key_exists('__lock', $data)) {
            $shard->isConnected() or $this->respondToInvalidArgument('Cannot lock table outside of a transaction. \%s::populate()');
            $shard->inTransaction() or $this->respondToInvalidArgument('Cannot lock table outside of a transaction. \%s::populate()');
        }
        
        // SORTING
        if (array_key_exists('__sorting', $data)) {
            is_array($data['__sorting']) && count($data['__sorting']) or $this->respondToInvalidArgument('Incorrect sorting data. \%s::populate()');

            foreach ($data['__sorting'] as $val) {
                // check structure and sort type
                !is_array($val) || count($val) !== 2 || ($val[1] !== 'ASC' && $val[1] !== 'DESC') and $this->respondToInvalidArgument('Incorrect sorting data. \%s::populate()');

                // check column validity
                in_array($val[0], $this->getSortColumns()) or $this->respondToInvalidArgument('Incorrect sorting data. \%s::populate()');
            }
        }
        
        // SUBSET
        if (array_key_exists('__subset', $data)) {
            is_array($data['__subset']) or $this->respondToInvalidArgument('Incorrect subset data. \%s::populate()');
            !isset($data['__subset']['limit']) || !is_int($data['__subset']['limit']) || $data['__subset']['limit'] < 1 and $this->respondToInvalidArgument('Incorrect subset data. \%s::populate()');
            !isset($data['__subset']['offset']) || !is_int($data['__subset']['offset']) || $data['__subset']['offset'] < 0 and $this->respondToInvalidArgument('Incorrect subset data. \%s::populate()');
        }
        
        // DISTINCT
        if (array_key_exists('__distinct', $data)) is_array($data['__distinct']) && count($data['__distinct']) or $this->respondToInvalidArgument('Incorrect __distinct data. \%s::populate()');
        
        // SUM + COUNT
        if (array_key_exists('__count', $data) && array_key_exists('__sum', $data)) $this->respondToInvalidArgument('__count and __sum are mutually exclusive. \%s::populate()');
        
        // COUNT
        if (array_key_exists('__count', $data)) is_array($data['__count']) && count($data['__count']) or $this->respondToInvalidArgument('Incorrect __count data. \%s::populate()');
        
        // SUM 
        if (array_key_exists('__sum', $data)) is_array($data['__sum']) && count($data['__sum']) or $this->respondToInvalidArgument('Incorrect __sum data. \%s::populate()');
        
        return $this;
    }
    
    /**
     * Initialize the query with a proper prepend section. By default the prepend section is empty and should be
     * overloaded when necessary.
     *
     * @param \stdClass $dto
     * @return string
     */
    protected function populate_prependSection(\stdClass $dto): string {
        return '';
    }

    /**
     * Fetch initial populate query segment.
     *
     * @param string $query
     * @param \stdClass $dto
     * @param string $table
     * @return string
     */
    protected function populate_basicQuery(string $query, \stdClass $dto, string $table): string {
        // add basic select clause
        $query .= 'SELECT ';

        // add distinct values if requested
        if (array_key_exists('__distinct', $dto->data)) {
            $query .= 'DISTINCT ON("';
            $query .= implode('", "', $dto->data['__distinct']);
            $query .= '") ';

            unset($dto->data['__distinct']);
        }

        // add grouping/counting clauses
        if (array_key_exists('__count', $dto->data)) {
            if (!array_key_exists('__distinct', $dto->data['__count'])) {
                $query .= 'COUNT("' . array_shift($dto->data['__count']) . '") AS "__count" ';
            } else {
                unset($dto->data['__count']['__distinct']);
                $query .= 'COUNT(DISTINCT "' . array_shift($dto->data['__count']) . '") AS "__count" ';
            }
            
            count($dto->data['__count']) and $query .= ', "' . implode('", "', $dto->data['__count']) . '" ';
        } elseif (array_key_exists('__sum', $dto->data)) {
            $query .= 'SUM("' . array_shift($dto->data['__sum']) . '") AS "__sum" ';
            count($dto->data['__sum']) and $query .= ', "' . implode('", "', $dto->data['__sum']) . '" ';
        } else {
            $query .= '* ';
        }

        // add basic FROM clause
        $query .= 'FROM "' . $table . '" WHERE ';

        return $query;
    }
    
    /**
     * Fetch a query with filter syntax attached.
     *
     * @param string    $query
     * @param \stdClass $dto
     * @return string
     */
    protected function populate_attachFilter(string $query, \stdClass $dto): string {
        foreach ($dto->data as $key => $data) {
            // skip any special tokens
            if ($key[0] === '_' && $key[1]) continue;

            // validate token data
            is_array($data) && count($data) or $this->respondToInvalidArgument('Incorrect filter param provided [' . $key . '], non-empty array expected. \%s::populate()');

            // parse key for filters
            $structure = $this->parseFilter($key);

            // create a list of statement tokens and matching values
            $temp = [];
            $hax_values = [false, 0, "0"]; // PHP sucks and empty(any of these values) returns true...
            foreach ($data as $elK => $elV) {
                (empty($elV) && !in_array($elV, $hax_values)) and $this->respondToInvalidArgument('Incorrect filter value [' . $key . '] - non empty value expected. \%s::populate()');
                $dto->params[$structure['prefix'] . $elK] = $elV;
                $temp[] = $structure['value_prefix'] . $structure['prefix'] . $elK . $structure['value_suffix'];
            }

            // attach filter to the query
            $query .= '' . $structure['column'] . ' ' . $structure['operator'] . ' (' . implode(', ', $temp) . ') AND ';
        }

        return $query;
    }
    
    /**
     * Fetch the blanket conditional SQL query segment.
     *
     * @param string $query
     * @return string
     */
    protected function populate_blanketSQL(string $query): string {
        return $query . '1=1 ';
    }

    /**
     * Attach grouping section to the query.
     * 
     * @param string $query
     * @param \stdClass $dto
     * @return string
     */
    protected function populate_grouping(string $query, \stdClass $dto): string {
        if (array_key_exists('__count', $dto->data) && is_array($dto->data['__count']) && count($dto->data['__count'])) {
            $query .= 'GROUP BY "' . implode('", "', $dto->data['__count']) . '" ';
        }

        if (array_key_exists('__sum', $dto->data) && is_array($dto->data['__sum']) && count($dto->data['__sum'])) {
            $query .= 'GROUP BY "' . implode('", "', $dto->data['__sum']) . '" ';
        }

        return $query;
    }

    /**
     * Attach sorting section to the query.
     * 
     * @param string $query
     * @param \stdClass $dto
     * @return string
     */
    protected function populate_sorting(string $query, \stdClass $dto): string {
        $query .= 'ORDER BY ';
        $fields = [];
        foreach ($dto->data['__sorting'] as $val) {
            $fields[] = "\"$val[0]\" $val[1]";
        }
        $query .= implode(', ', $fields) . ' ';

        return $query;
    }
    
    /**
     * Attach subset section to the query.
     * 
     * @param string $query
     * @param \stdClass $dto
     * @return string
     */
    protected function populate_subset(string $query, \stdClass $dto): string {
        $query .= 'LIMIT :limit OFFSET :offset ';
        $dto->params[':limit'] = $dto->data['__subset']['limit'];
        $dto->params[':offset'] = $dto->data['__subset']['offset'];

        return $query;
    }

    /**
     * Attach locking section to the query.
     * 
     * @param string $query
     * @param \stdClass $dto
     * @return string
     */
    protected function populate_lock(string $query, \stdClass $dto): string {
        array_key_exists('__lock', $dto->data) and $query .= 'FOR UPDATE ';

        return $query;
    }
    
    /* ------------------------------------ Populate Methods END --------------------------------------- */

    /* ------------------------------------ Helper methods START --------------------------------------- */

    /**
     * Parse provided key param into a set of filtering data.
     *
     * @param string $key
     * @return array
     */
    private function parseFilter(string $key): array {
        $result = ['column' => '"' . $key . '"', 'operator' => 'IN', 'prefix' => ':' . $key . '_', 'value_prefix' => '', 'value_suffix' => ''];

        // attempt to recover filters
        $data = explode('/', $key);

        // filters detected
        if (count($data) > 1) {
            // fetch filters
            $filter = array_shift($data);

            // establish a new basic result structure
            $result = ['column' => '"' . $data[0] . '"', 'operator' => $result['operator'], 'prefix' => ':' . $data[0] . '_', 'value_prefix' => $result['value_prefix'], 'value_suffix' => $result['value_suffix']];

            // apply filters
            for ($index = 0; $index < mb_strlen($filter); $index++) {
                // exclude filter
                $filter[$index] === '~' and $result = [
                    'column' => $result['column'],
                    'operator' => 'NOT IN',
                    'prefix' => $result['prefix'] . 'exclude_',
                    'value_prefix' => $result['value_prefix'],
                    'value_suffix' => $result['value_suffix'],
                ];

                // case-insensitive filter
                $filter[$index] === 'i' and $result = [
                    'column' => 'LOWER(' . $result['column'] . ')',
                    'operator' => $result['operator'],
                    'prefix' => $result['prefix'] . 'case_insensitive_',
                    'value_prefix' => 'LOWER(' . $result['value_prefix'],
                    'value_suffix' => $result['value_suffix'] . ')',
                ];
            }
        }

        return $result;
    }
    
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
}