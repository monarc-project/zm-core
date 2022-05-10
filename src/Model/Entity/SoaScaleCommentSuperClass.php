<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="soa_scale_comments", indexes={
 *      @ORM\Index(name="soa_scale_id", columns={"scale_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class SoaScaleCommentSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;


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
     * @var SoaScaleSuperClass
     *
     * @ORM\ManyToOne(targetEntity="SoaScale", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="soa_scale_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $soaScale;


    /**
     * @var int
     *
     * @ORM\Column(name="scale_index", type="integer", options={"unsigned": true})
     */
    protected $scaleIndex;

    /**
     * @var string
     *
     * @ORM\Column(name="comment_translation_key", type="string", length=255, options={"default": ""})
     */
    protected $commentTranslationKey = '';

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"default": 0})
     */
    protected $isHidden = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getAnr(): AnrSuperClass
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getSoaScale(): OperationalRiskScaleSuperClass
    {
        return $this->soaScale;
    }

    public function setSoaScale(OperationalRiskScaleSuperClass $soaScale): self
    {
        $this->soaScale = $soaScale;
        $soaScale->addSoaScaleComments($this);

        return $this;
    }

    public function getScaleIndex(): int
    {
        return $this->scaleIndex;
    }

    public function setScaleIndex(int $scaleIndex): self
    {
        $this->scaleIndex = $scaleIndex;

        return $this;
    }

    public function getCommentTranslationKey(): string
    {
        return $this->commentTranslationKey;
    }

    public function setCommentTranslationKey(string $commentTranslationKey): self
    {
        $this->commentTranslationKey = $commentTranslationKey;

        return $this;
    }

    public function isHidden(): bool
    {
        return (bool)$this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = (int)$isHidden;

        return $this;
    }
}
