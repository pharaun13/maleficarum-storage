<?php
/**
 * This interface must be implemented by all Maleficarum Storage model repositories.
 */
declare (strict_types=1);

namespace Maleficarum\Storage\Repository;

interface Model {
    /**
     * Persist the specified model a new storage layer entity.
     *
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\Model
     */
    public function create(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model;

    /**
     * Fill the specified model with data recovered from an existing storage layer entity.
     *
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\Model
     */
    public function read(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model;

    /**
     * Update an exiting storage layer entity using the specified model.
     *
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\Model
     */
    public function update(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model;

    /**
     * Delete an existing storage layer entity based on the specified model object.
     * 
     * @param \Maleficarum\Data\Model\Persistable\AbstractModel $model
     * @return \Maleficarum\Storage\Repository\Model
     */
    public function delete(\Maleficarum\Data\Model\Persistable\AbstractModel $model): \Maleficarum\Storage\Repository\Model;
}