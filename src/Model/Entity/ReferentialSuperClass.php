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
use Ramsey\Uuid\Lazy\LazyUuidFromString;

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
     * @var LazyUuidFromString|string
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
     * @var MeasureSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Measure", mappedBy="referential", cascade={"persist"})
     */
    protected $measures;

    /**
     * @var SoaCategorySuperClass[]
     *
     * @ORM\OneToMany(targetEntity="SoaCategory", mappedBy="referential", cascade={"persist"})
     */
    protected $categories;

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return self
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return MeasureSuperClass[]
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * @param MeasureSuperClass[] $measures
     */
    public function setMeasures($measures): self
    {
        $this->measures = $measures;

        return $this;
    }

    /**
     * @return SoaCategorySuperClass[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param SoaCategorySuperClass[] $categories
     */
    public function setCategories($categories): self
    {
        $this->categories = $categories;

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
        }
        return $this->inputFilter;
    }
}
