<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Amv
 *
 * @ORM\Table(name="amvs", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="asset", columns={"asset_id"}),
 *      @ORM\Index(name="threat", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability", columns={"vulnerability_id"}),
 *      @ORM\Index(name="measure1", columns={"measure1_id"}),
 *      @ORM\Index(name="measure2", columns={"measure2_id"}),
 *      @ORM\Index(name="measure3", columns={"measure3_id"})
 * })
 * @ORM\Entity
 */
class Amv extends AmvSuperclass
{
}
