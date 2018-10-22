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
    * @var text
    *
    * @ORM\Column(name="control1", type="text", length=255, nullable=true)
    */
    protected $control1 ;

    /**
    * @var text
    *
    * @ORM\Column(name="control2", type="text", length=255, nullable=true)
    */
    protected $control2 ;

    /**
    * @var text
    *
    * @ORM\Column(name="control3", type="text", length=255, nullable=true)
    */
    protected $control3 ;

    /**
    * @var text
    *
    * @ORM\Column(name="control4", type="text", length=255, nullable=true)
    */
    protected $control4 ;

    /**
    * @var text
    *
    * @ORM\Column(name="requirement", type="text", length=255, nullable=true)
    */
    protected $requirement ;

    /**
    * @var text
    *
    * @ORM\Column(name="justification", type="text", length=255, nullable=true)
    */
    protected $justification ;

    /**
    * @var text
    *
    * @ORM\Column(name="evidences", type="text", length=255, nullable=true)
    */
    protected $evidences ;

    /**
    * @var text
    *
    * @ORM\Column(name="actions", type="text", length=255, nullable=true)
    */
    protected $actions ;

    /**
    * @var string
    *
    * @ORM\Column(name="compliance", type="string", length=255, nullable=true)
    */
    protected $compliance ;

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

    /**
    * @return TEXT_LONG
    */
    public function getDescription()
    {
        return $this->description;
    }

    /**
    * @param TEXT_LONG $description
    *
    */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
    * @return TEXT_LONG
    */
    public function getControl1()
    {
        return $this->control1;
    }

    /**
    * @param TEXT_LONG $control1
    *
    */
    public function setControl1($control1)
    {
        $this->control1 = $control1;
    }

    /**
    * @return TEXT_LONG
    */
    public function getControl2()
    {
        return $this->control;
    }

    /**
    * @param TEXT_LONG $control2
    *
    */
    public function setControl2($control2)
    {
        $this->control2 = $control2;
    }

    /**
    * @return TEXT_LONG
    */
    public function getControl3()
    {
        return $this->control3;
    }

    /**
    * @param TEXT_LONG $control3
    *
    */
    public function setControl3($control3)
    {
        $this->control3 = $control3;
    }

    /**
    * @return TEXT_LONG
    */
    public function getControl4()
    {
        return $this->control4;
    }

    /**
    * @param TEXT_LONG $control4
    *
    */
    public function setControl4($control4)
    {
        $this->control4 = $control4;
    }

    /**
    * @return TEXT_LONG
    */
    public function getRequirement()
    {
        return $this->requirement;
    }

    /**
    * @param TEXT_LONG $requirement
    *
    */
    public function setRequirement($requirement)
    {
        $this->requirement = $requirement;
    }

    /**
    * @return TEXT_LONG
    */
    public function getJustification()
    {
        return $this->justification;
    }

    /**
    * @param TEXT_LONG $justification
    *
    */
    public function setJustification($justification)
    {
        $this->justification = $justification;
    }


    /**
    * @return TEXT_LONG
    */
    public function getEvidences()
    {
        return $this->evidences;
    }

    /**
    * @param TEXT_LONG $evidences
    *
    */
    public function setEvidences($evidences)
    {
        $this->evidences = $evidences;
    }


    /**
    * @return TEXT_LONG
    */
    public function getActions()
    {
        return $this->actions;
    }

    /**
    * @param TEXT_LONG $actions
    *
    */
    public function setActions($actions)
    {
        $this->actions = $actions;
    }

    /**
    * @return string
    */
    public function getCompliance()
    {
        return $this->compliance;
    }

    /**
    * @param string $compliance
    *
    */
    public function setCompliance($compliance)
    {
        $this->compliance = $compliance;
    }

    /**
    * @return boolean
    */
    public function isIsDeleted()
    {
        return $this->isDeleted;
    }
}
