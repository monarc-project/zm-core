<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Entity\Traits\LabelsEntityTrait;
use Monarc\Core\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="questions_choices", indexes={
 *      @ORM\Index(name="question_id", columns={"question_id"}),
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class QuestionChoiceSuperClass extends AbstractEntity
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
     * @var QuestionSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Question", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $question;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true})
     */
    protected $position;

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

    public function getQuestion()
    {
        return $this->question;
    }

    public function setQuestion(QuestionSuperClass $question): self
    {
        $this->question = $question;
        $question->addQuestionChoice($this);

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $labels = [
                'label1', 'label2', 'label3', 'label4'
            ];

            foreach ($labels as $label) {
                $this->inputFilter->add(array(
                    'name' => $label,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            //For this class, the position is handle manually
            $this->inputFilter->add(array(
                'name' => 'position',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => [['name' => 'ToInt']],
                'validators' => array()
            ));
        }

        return $this->inputFilter;
    }
}
