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
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
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

    public function getInputFilter(){
        if (!$this->inputFilter) {
            parent::getInputFilter();
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
                    array('name' => 'EmailAddress',),
                    array(
                        'name' => '\MonarcCore\Validator\UniqueEmail',
                        'options' => array(
                            'adapter' => $this->getDbAdapter(),
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

    public function exchangeArray(array $options)
    {
        $filter = $this->getInputFilter()
            ->setData($options)
            ->setValidationGroup(InputFilterInterface::VALIDATE_ALL);
        $isValid = $filter->isValid();
        if(!$isValid){
            // TODO: ici on pourrait remonter la liste des champs qui ne vont pas
            throw new \Exception("Invalid data set");
        }
        $options = $filter->getValues();
        if(empty($options['password'])){
            unset($options['password']);
        }
        foreach($options as $k => $v){
            $this->set($k,$v);
        }
        return $this;
    }
}

