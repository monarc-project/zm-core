<?php
namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scale Type
 *
 * @ORM\Table(name="scales_impact_types", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="scale_id", columns={"scale_id"}),
 * })
 * @ORM\Entity
 */
class ScaleImpactType extends ScaleImpactTypeSuperClass
{
}

