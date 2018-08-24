<?php
/**
 * This exceptions represents cases when a repository was asked to read a nonexistent entity.
 */
declare(strict_types=1);

namespace Maleficarum\Storage\Exception\Repository;

final class EntityNotFoundException extends \Exception {
    /* ------------------------------------ Class Property START --------------------------------------- */
    
    /**
     * @var string
     */
    private $modelClassName;

    /**
     * @var string
     */
    private $entityId;

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Magic methods START ---------------------------------------- */
    
    /**
     * @param string         $modelClassName eg. 'Model\Store\Product', you can pass value of `static::class`
     * @param string         $entityId       eg. '1247'
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $modelClassName, string $entityId, int $code = 0, \Throwable $previous = null) {
        $this->modelClassName = $modelClassName;
        $this->entityId = $entityId;

        $message = "No entity found - ID: {$entityId} . Model class: {$modelClassName}";
        parent::__construct($message, $code, $previous);
    }

    /* ------------------------------------ Magic methods END ------------------------------------------ */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */
    
    /**
     * @return string name of the model class that entity has not been found for; eg. 'Model\Store\Product'
     */
    public function getModelClassName(): string {
        return $this->modelClassName;
    }

    /**
     * @return string Id of the model/entity that has not been found in the database; eg. '1247'
     */
    public function getEntityId(): string {
        return $this->entityId;
    }
    
    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}