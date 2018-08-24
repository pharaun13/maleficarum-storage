<?php
/**
 * This a single entity repository for Redis storage layer.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository\Redis;

class Model implements \Maleficarum\Storage\Repository\Model {
    /* ------------------------------------ Class Traits START ----------------------------------------- */

    use \Maleficarum\Storage\Dependant;

    /* ------------------------------------ Class Traits END ------------------------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */
    
    /**
     * @see \Maleficarum\Storage\Repository\Model.create()
     */
    public function create(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model {
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