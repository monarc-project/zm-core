<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* Soa
*
* @ORM\Table(name="soa")
* @ORM\Entity
*/
class Soa extends AbstractEntity
{
    /**
    * @var integer
    *
    * @ORM\Column(name="id", type="integer", nullable=false)
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="IDENTITY")
    */
    protected $id;

    /**
    * @var integer
    *
    *  @ORM\Column(name="measure_id", type="integer",  nullable=false)
    */
    protected $measure;

    /**
    * @var string
    *
    * @ORM\Column(name="reference", type="string", length=255, nullable=true)
    */
    protected $reference;

    /**
    * @return int
    */
    public function getId()
    {
        return $this->id;
    }

    /**
    * @param int $id
    *
    */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
    * @return int
    */
    public function getMeasure()
    {
        return $this->measure;
    }

    /**
    * @param int $measure
    *
    */
    public function setMeasure($measure)
    {
        $this->measure = $measure;
    }

    /**
    * @return string
    */
    public function getReference()
    {
        return $this->reference;
    }

    /**
    * @param string $reference
    *
    */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }
}
