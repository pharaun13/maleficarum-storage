<?php
/**
 * This is a generic PDO statement used by Maleficarum PDO postgresql connections.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Shard\Postgresql\PDO\Statement;

class Generic extends \PDOStatement {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Internal storage for the attached PDO object.
     *
     * @var \PDO
     */
    protected $pdo = null;
    
    /* ------------------------------------ Class Property END ----------------------------------------- */
    
    /* ------------------------------------ Magic methods START ---------------------------------------- */

    /**
     * Initialize a new Statement instance and allow for trailer injection.
     *
     * @param \PDO     $pdo
     */
    protected function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /* ------------------------------------ Magic methods END ------------------------------------------ */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Bind value to the specified token.
     *
     * @param mixed $parameter
     * @param mixed $value
     * @param int $data_type
     *
     * @return mixed
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR) {
        parent::bindValue($parameter, $value, is_bool($value) ? \PDO::PARAM_BOOL : $data_type);
    }
    
    /**
     * By default fetch assoc.
     *
     * @param int $how
     * @param int $orientation
     * @param int $offset
     *
     * @return mixed
     */
    public function fetch($how = \PDO::FETCH_ASSOC, $orientation = \PDO::FETCH_ORI_NEXT, $offset = 0) {
        return parent::fetch($how, $orientation, $offset);
    }

    /**
     * By default fetch assoc.
     *
     * @param int   $fetch_style
     * @param int   $fetch_argument
     * @param array $ctor_args
     *
     * @return mixed
     */
    public function fetchAll($fetch_style = \PDO::FETCH_ASSOC, $fetch_argument = null, $ctor_args = null) {
        return parent::fetchAll($fetch_style);
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}