<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Annotation\Entity;

use Attribute;

/**
 * Class OneToOne
 *
 * Annotation to define a one-to-one relationship for an entity property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToOne
{
    /**
     * @var string The target entity class name.
     */
    public $targetEntity;

    /**
     * @var string|null The property name on the target entity that maps back to this entity.
     */
    public $mappedBy;

    /**
     * @var string|null The property name on this entity that is inversed by the target entity.
     */
    public $inversedBy;

    /**
     * OneToOne constructor.
     *
     * @param string $targetEntity The target entity class name.
     * @param string|null $mappedBy The property name on the target entity that maps back to this entity.
     * @param string|null $inversedBy The property name on this entity that is inversed by the target entity.
     */
    public function __construct(string $targetEntity, ?string $mappedBy = null, ?string $inversedBy = null)
    {
        $this->targetEntity = $targetEntity;
        $this->mappedBy = $mappedBy;
        $this->inversedBy = $inversedBy;
    }
}
