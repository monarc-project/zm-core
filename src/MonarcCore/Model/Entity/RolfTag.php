<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Thme
 *
 * @ORM\Table(name="rolf_tags", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class RolfTag extends RolfTagSuperclass
{
    /**
     * @var \MonarcCore\Model\Entity\RolfRisk
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\RolfRisk", mappedBy="tags", cascade={"persist"})
     */
    protected $risks;
}

