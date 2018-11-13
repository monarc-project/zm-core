<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Measure
 *
 * @ORM\Table(name="measures", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="soacategory", columns={"soacategory_id"}),
 *      @ORM\Index(name="referential", columns={"referential_uniqid"})
 * })
 * @ORM\Entity
 */
class Measure extends MeasureSuperClass
{
  /**
   * @param mixed $obj (extends AbstractEntity OR array)
   */
  public function __construct()
  {
    $this->measuresLinked = new \Doctrine\Common\Collections\ArrayCollection();
    $this->measuresLinkedToMe = new \Doctrine\Common\Collections\ArrayCollection();
  }
  /**
   * @var \Doctrine\Common\Collections\ArrayCollection
   * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Measure", mappedBy="measuresLinked")
   */
  private $measuresLinkedToMe;

  /**
    * @var \Doctrine\Common\Collections\ArrayCollection
   * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Measure", inversedBy="measuresLinkedToMe")
   * @ORM\JoinTable(name="measures_measures",
   *     joinColumns={@ORM\JoinColumn(name="father_id", referencedColumnName="id")},
   *     inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id")}
   * )
   */
   private $measuresLinked;
}
