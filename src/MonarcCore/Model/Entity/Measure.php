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
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class Measure extends MeasureSuperClass
{

//  /**
//  * @var \MonarcCore\Model\Entity\Soa
//  *
//  * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Soa", inversedBy="")
//  */
//  protected $Soa;


//  /**
//   * @var \MonarcCore\Model\Entity\Threat
//   *
//   * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Threat", inversedBy="id")
//   */
//  protected $Threat;



}
