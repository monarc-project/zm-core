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
use Ramsey\Uuid\Uuid;

/**
 * Threat
 *
 * @ORM\Table(name="threats", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id","code"}),
 *      @ORM\Index(name="anr_id2", columns={"anr_id"}),
 *      @ORM\Index(name="theme_id", columns={"theme_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class ThreatSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    /**
    * @var LazyUuidFromString|string
    *
    * @ORM\Column(name="uuid", type="uuid", nullable=false)
    * @ORM\Id
    */
    protected $uuid;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $anr;

    /**
     * @var ThemeSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Theme", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="theme_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $theme;

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
     * @var string
     *
     * @ORM\Column(name="description1", type="string", length=255, nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="string", length=255, nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="string", length=255, nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="string", length=255, nullable=true)
     */
    protected $description4;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="mode", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $mode = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @var int
     *
     * @ORM\Column(name="trend", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $trend = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="qualification", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $qualification = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $c = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $i = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="a", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $a = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\PrePersist
     */
    public function generateAndSetUuid(): self
    {
        if ($this->uuid === null) {
            $this->uuid = Uuid::uuid4();
        }

        return $this;
    }

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
     * @return AnrSuperClass
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param AnrSuperClass $anr
     */
    public function setAnr($anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function setTheme(ThemeSuperClass $theme)
    {
        $this->theme = $theme;
    }

    public function getTheme(): ?ThemeSuperClass
    {
        return $this->theme;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setLabels(array $labels): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'label' . $index;
            if (isset($labels[$key])) {
                $this->{$key} = $labels[$key];
            }
        }

        return $this;
    }

    public function getLabel(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
    }

    public function setDescriptions(array $descriptions): self
    {
        foreach (range(1, 4) as $index) {
            $key = 'description' . $index;
            if (isset($descriptions[$key])) {
                $this->{$key} = $descriptions[$key];
            }
        }

        return $this;
    }

    public function getDescription(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'description' . $languageIndex};
    }

    public function setConfidentiality(int $c): self
    {
        $this->c = $c;

        return $this;
    }

    public function getConfidentiality(): int
    {
        return $this->c;
    }

    public function setIntegrity(int $i): self
    {
        $this->i = $i;

        return $this;
    }

    public function getIntegrity(): int
    {
        return $this->i;
    }

    public function setAvailability(int $a): self
    {
        $this->a= $a;

        return $this;
    }

    public function getAvailability(): int
    {
        return $this->a;
    }

    public function getStatus(): int
    {
        return (int)$this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return (int)$this->mode;
    }

    public function setMode(int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getTrend(): int
    {
        return (int)$this->trend;
    }

    public function setTrend(int $trend): self
    {
        $this->trend = $trend;

        return $this;
    }

    public function getQualification(): int
    {
        return (int)$this->qualification;
    }

    public function setQualification(int $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getComment(): string
    {
        return (string)$this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

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
                    'required' => ((strstr($text, (string)$this->getLanguage())) && (!$partial)) ? true : false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $descriptions = ['description1', 'description2', 'description3', 'description4'];
            foreach ($descriptions as $description) {
                $this->inputFilter->add(array(
                    'name' => $description,
                    'required' => false,
                    'allow_empty' => true,
                    'filters' => array(),
                    'validators' => array(),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'c',
                'required' => false,
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
            $this->inputFilter->add(array(
                'name' => 'i',
                'required' => false,
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
            $this->inputFilter->add(array(
                'name' => 'a',
                'required' => false,
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

            $this->inputFilter->add(array(
                'name' => 'code',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(),
            ));

            $this->inputFilter->add(array(
                'name' => 'mode',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(),
            ));

            $this->inputFilter->add(array(
                'name' => 'status',
                'required' => false,
                'allow_empty' => false,
                'filters' => array(
                    array('name' => 'ToInt'),
                ),
                'validators' => array(
                    array(
                        'name' => 'InArray',
                        'options' => array(
                            'haystack' => array(self::STATUS_INACTIVE, self::STATUS_ACTIVE),
                        ),
                    ),
                ),
            ));

            $validatorsCode = [];
            if (!$partial) {
                $validatorsCode = array(
                    array(
                        'name' => 'Monarc\Core\Validator\UniqueCode',
                        'options' => array(
                            'entity' => $this
                        ),
                    ),
                );
            }

            $this->inputFilter->add(array(
                'name' => 'code',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'filters' => array(),
                'validators' => $validatorsCode
            ));
        }
        return $this->inputFilter;
    }
}
