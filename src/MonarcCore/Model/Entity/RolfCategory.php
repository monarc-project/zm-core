<?php
namespace MonarcCore\Model\Entity;
use Doctrine\ORM\Mapping as ORM;
/**
 * Thme
 *
 * @ORM\Table(name="rolf_categories", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class RolfCategory extends RolfCategorySuperclass
{
}
