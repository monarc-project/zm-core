<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Object Category
 *
 * @ORM\Table(name="objects_categories", indexes={
 *      @ORM\Index(name="root_id", columns={"root_id"}),
 *      @ORM\Index(name="parent_id", columns={"parent_id"}),
 *      @ORM\Index(name="position", columns={"position"}),
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 * })
 * @ORM\Entity
 */
class ObjectCategory extends ObjectCategorySuperClass
{
}
