<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="questions")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class QuestionSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    use LabelsEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var QuestionChoiceSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="QuestionChoice", mappedBy="question")
     */
    protected $questionChoices;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="multichoice", type="smallint", options={"unsigned":true})
     */
    protected $multichoice;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true})
     */
    protected $position;

    public function __construct($obj = null)
    {
        parent::__construct($obj);

        $this->questionChoices = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getQuestionChoices()
    {
        return $this->questionChoices;
    }

    public function addQuestionChoice(QuestionChoiceSuperClass $questionChoice): self
    {
        if (!$this->questionChoices->contains($questionChoice)) {
            $this->questionChoices->add($questionChoice);
            $questionChoice->setQuestion($this);
        }

        return $this;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition()
    {
        return (int)$this->position;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setIsMultiChoice(bool $isMultiChoice): self
    {
        $this->multichoice = (int)$isMultiChoice;

        return $this;
    }

    public function isMultiChoice(): bool
    {
        return $this->multichoice === 1;
    }

    // TODO: Related to the refactoring of all: questions order is a bit different.
    protected $parameters = [
        'isParentRelative' => false // for the autopositionner
    ];

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add([
                'name' => 'type',
                'required' => $partial ? false : true,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => [],
                'validators' => [
                    [
                        'name' => 'InArray',
                        'options' => [
                            'haystack' => [1, 2],
                        ],
                        'default' => 1,
                    ],
                ],
            ]);

            $this->inputFilter->add([
                'name' => 'multichoice',
                'required' => $partial ? false : true,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => [],
                'validators' => [
                    [
                        'name' => 'InArray',
                        'options' => [
                            'haystack' => [0, 1],
                        ],
                        'default' => 0,
                    ],
                ],
            ]);
        }

        return $this->inputFilter;
    }
}
