<?php declare(strict_types=1);

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="translations",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="key_lang_unq", columns={"key", "lang"})
 *   }
 * )
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class TranslationSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const OPERATIONAL_RISK_SCALE_TYPE = 'operational-risk-scale-type';
    public const OPERATIONAL_RISK_SCALE_COMMENT = 'operational-risk-scale-comment';
    public const ANR_INSTANCE_METADATA_FIELD = 'anr-instance-metadata-field';
    public const SOA_SCALE_COMMENT = 'soa-scale-comment';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_key", type="string", length=255)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=2, options={"fixed"=true})
     */
    protected $lang;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=false)
     */
    protected $value;

    public static function constructFromObject(TranslationSuperClass $translation): TranslationSuperClass
    {
        return (new static())
            ->setType($translation->getType())
            ->setKey($translation->getKey())
            ->setLang($translation->getLang())
            ->setValue($translation->getValue());
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAnr(): ?AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
