<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Question's choices
 *
 * @ORM\Table(name="questions_choices", indexes={
 *      @ORM\Index(name="question_id", columns={"question_id"}),
 * })
 * @ORM\MappedSuperclass
 */
class QuestionChoiceSuperclass extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Question
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Question")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $question;

    /**
     * @var \MonarcCore\Model\Entity\Translation
     *
     * @ORM\ManyToMany(targetEntity="\MonarcCore\Model\Entity\Translation")
     * @ORM\Column(name="label_translation_id")
     * @ORM\JoinTable(name="translation_language",
     *     joinColumns={@ORM\JoinColumn(name="questions_choices_string_id", referencedColumnName="label_translation_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="translation_id", referencedColumnName="id")})
     *
     */
    protected $label;

    /**
     * @var smallint
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true})
     */
    protected $position;

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255, nullable=true)
     */
    protected $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="updater", type="string", length=255, nullable=true)
     */
    protected $updater;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Question
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param Question $question
     * @return QuestionChoice
     */
    public function setQuestion($question)
    {
        $this->question = $question;
        return $this;
    }

    // Don't need this, the entity is really simple, position is handled manually
    // protected $parameters = array(
    //     'implicitPosition' => array(
    //         'field' => 'question',
    //     ),
    // );

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
            /*
                        $this->inputFilter->add(array(
                            'name' => 'question',
                            'required' => true,
                            'allow_empty' => false,
                            'filters' => array(),
                            'validators' => array(
                                array(
                                    'name' => 'IsInt',
                                ),
                            ),
                        ));
            */
        }
        return $this->inputFilter;
    }
}

