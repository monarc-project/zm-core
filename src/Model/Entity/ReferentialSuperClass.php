<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Ramsey\Uuid\UuidInterface;

/**
 * ReferentialSuperClass
 *
 * @ORM\Table(name="referentials")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ReferentialSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    /**
     * The uuid or the referential.
     *
     * @var \Ramsey\Uuid\UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(name="uuid", type="uuid", unique=true)
     */
    protected $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="label1", type="string", length=255, nullable=true)
     */
    protected $label1;

    /**
     * @var string
     *
     * @ORM\Column(name="label2", type="string", length=255, nullable=true)
     */
    protected $label2;

    /**
     * @var string
     *
     * @ORM\Column(name="label3", type="string", length=255, nullable=true)
     */
    protected $label3;

    /**
     * @var string
     *
     * @ORM\Column(name="label4", type="string", length=255, nullable=true)
     */
    protected $label4;

    /**
     * @var \Monarc\Core\Model\Entity\Measure
     *
     * @ORM\OneToMany(targetEntity="Monarc\Core\Model\Entity\Measure", mappedBy="referential", cascade={"persist"})
     */
    protected $measures;

    /**
     * @var \Monarc\Core\Model\Entity\SoaCategory
     *
     * @ORM\OneToMany(targetEntity="Monarc\Core\Model\Entity\SoaCategory", mappedBy="referential", cascade={"persist"})
     */
    protected $categories;

    /**
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface $uuid
     * @return Referential
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return Measure
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * @param \Monarc\Core\Model\Entity\Measure $measures
     * @return Referential
     */
    public function setMeasures($measures)
    {
        $this->measures = $measures;
        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['label1', 'label2', 'label3', 'label4'];
            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => strpos($text, (string)$this->getLanguage()) !== false && !$partial,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }
            // $validatorsCode = [];
            // if (!$partial) {
            //     $validatorsCode = array(
            //         array(
            //             'name' => 'Monarc\Core\Validator\UniqueCode',
            //             'options' => array(
            //                 'entity' => $this
            //             ),
            //         ),
            //     );
            // }

            // $this->inputFilter->add(array(
            //     'name' => 'uuid',
            //     'required' => true,
            //     'allow_empty' => false,
            //     'filters' => array(),
            //     // 'validators' => $validatorsCode
            // ));
        }
        return $this->inputFilter;
    }
}
