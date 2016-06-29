<?php

namespace MonarcCore\Model\Entity;

use Zend\InputFilter\InputFilterInterface;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})})
 * @ORM\Entity
 */
class User extends AbstractEntity
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
     * @var \DateTime
     *
     * @ORM\Column(name="date_start", type="date", nullable=true)
     */
    protected $dateStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_end", type="date", nullable=true)
     */
    protected $dateEnd;

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", nullable=true)
     */
    protected $status = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    protected $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    protected $password;

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

    public function getInputFilter($required = false){
        if (!$this->inputFilter) {
            parent::getInputFilter($required);
            $this->inputFilter->add(array(
                'name' => 'firstname',
                'required' => true,
                'filters' => array(
                    array('name' => 'StringTrim',),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'lastname',
                'required' => true,
                'filters' => array(
                    array('name' => 'StringTrim',),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'email',
                'required' => true,
                'filters' => array(
                    array('name' => 'StringTrim',),
                ),
                'validators' => array(
                    array('name' => 'EmailAddress'),
                    array(
                        /*'name' => 'DoctrineModule\Validator\NoObjectExists',
                        'options' => array(
                            'object_repository' => $entityManager->getRepository('\MonarcCore\Model\Table\UserTable'),
                            'fields' => 'email',
                            /*'exclude' => array(
                                'field' => 'id',
                                'value' => $this->get('id'),
                            ),
                        ),*/
                        'name' => '\MonarcCore\Validator\UniqueEmail',
                        'options' => array(
                            'adapter' => $this->getDbAdapter(),
                            'id' => $this->get('id'),
                        ),
                    ),
                ),
            ));
            $this->inputFilter->add(array(
                'name' => 'password',
                'allowEmpty' => true,
                'continueIfEmpty' => true,
                'required' => false,
                'filters' => array(
                    array(
                        'name' => '\MonarcCore\Filter\Password',
                        'options' => array(
                            'salt' => $this->getUserSalt(),
                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }

    public function setUserSalt($userSalt){
        $this->parameters['userSalt'] = $userSalt;
        return $this;
    }
    public function getUserSalt(){
        return isset($this->parameters['userSalt'])?$this->parameters['userSalt']:'';
    }
}

