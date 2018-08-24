<?php
/**
 * This trait provides functionalities common to all objects that rely on the storage component.
 */
declare (strict_types=1);

namespace Maleficarum\Storage;

trait DependantTrait {

    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Internal storage for the storage shard manager object.
     *
     * @var \Maleficarum\Storage\Manager
     */
    protected $storage = null;

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Get the currently assigned storage shard manager object.
     *
     * @return \Maleficarum\Storage\Manager|null
     */
    public function getStorage(): ?\Maleficarum\Storage\Manager {
        return $this->storage;
    }

    /**
     * Inject a new storage shard manager object.
     *
     * @param \Maleficarum\Storage\Manager $db
     * @return \Maleficarum\Storage\Dependant
     */
    public function setStorage(\Maleficarum\Storage\Manager $storage) {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Detach the current storage shard manager object.
     *
     * @return \Maleficarum\Storage\Dependant
     */
    public function detachStorage() {
        $this->storage = null;

        return $this;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}
