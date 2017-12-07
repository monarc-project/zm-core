<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Question
 *
 * @ORM\Table(name="questions")
 * @ORM\MappedSuperclass
 */
class QuestionSuperclass extends AbstractEntity
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
     * @var smallint
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var \MonarcCore\Model\Entity\Translation
     *
     * @ORM\ManyToMany(targetEntity="\MonarcCore\Model\Entity\Translation")
     * @ORM\Column(name="label_translation_id")
     * @ORM\JoinTable(name="translation_language",
     *     joinColumns={@ORM\JoinColumn(name="entity_string_id", referencedColumnName="label_translation_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="translation_id", referencedColumnName="id")})
     *
     */
    protected $label;

    /**
     * @var smallint
     *
     * @ORM\Column(name="multichoice", type="smallint", options={"unsigned":true})
     */
    protected $multichoice;

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

    protected $parameters = array(
        'isParentRelative' => false // for the autopositionner
    );


    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'type',
                'required' => ($partial) ? false : true,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [1, 2],
                        ),
                        'default' => 1,
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'multichoice',
                'required' => ($partial) ? false : true,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => [0, 1],
                        ),
                        'default' => 0,
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }
}

