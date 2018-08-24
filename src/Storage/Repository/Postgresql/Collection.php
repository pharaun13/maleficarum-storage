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

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.populate()
     */
    public function populate(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection, array $parameters = []): \Maleficarum\Storage\Repository\CollectionInterface {
        // connect to shard if necessary
        $shard = $this->getStorage()->fetchShard('Postgresql', $collection->getShardRoute());
        
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
        
        var_dump($query);exit;
        
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.createAll()
     */
    public function createAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface {
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.deleteAll()
     */
    public function deleteAll(\Maleficarum\Data\Collection\Persistable\AbstractCollection $collection): \Maleficarum\Storage\Repository\CollectionInterface {
        return $this;
    }

    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.transformForRetrieval()
     */
    public function transformForRetrieval(array $data): array {
        return [];
    }
    
    /**
     * @see \Maleficarum\Storage\Repository\CollectionInterface.Repository()
     */
    public function transformForPersistence(array $data): array {
        return [];
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
            if (in_array($key, self::$specialTokens)) {
                continue;
            }

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
    
    /* ------------------------------------ Populate Methods END --------------------------------------- */
}