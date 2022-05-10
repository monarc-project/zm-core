<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="soa_scale")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class SoaScaleSuperClass
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    public const TYPE_IMPACT = 1;
    public const TYPE_LIKELIHOOD = 2;

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
     * @var SoaScaleCommentSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SoaScaleComment", mappedBy="soaScale")
     * @ORM\OrderBy({"scaleIndex" = "ASC"})
     */
    protected $soaScaleComments;

    /**
     * @var int
     *
     * @ORM\Column(name="number_of_levels", type="smallint", options={"unsigned":true})
     */
    protected $numberOfLevels;


    public function __construct()
    {
        $this->soaScaleComments = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AnrSuperClass
     */
    public function getAnr()
    {
        return $this->anr;
    }

    public function setAnr(AnrSuperClass $anr): self
    {
        $this->anr = $anr;

        return $this;
    }

    public function getNumberOfLevels(): int
    {
        return $this->numberOfLevels;
    }

    public function setNumberOfLevels(int $numberOfLevels): self
    {
        $this->numberOfLevels = $numberOfLevels;

        return $this;
    }

    public function getSoaScaleComments()
    {
        return $this->soaScaleComments;
    }

    public function addSoaScaleComments(
        SoaScaleCommentSuperClass $soaScaleComment
    ): self {
        if (!$this->soaScaleComments->contains($soaScaleComment)) {
            $this->soaScaleComments->add($soaScaleComment);
            $soaScaleComment->setSoaScale($this);
        }

        return $this;
    }

    /**
     * @param SoaScaleCommentSuperClass[] $operationalRiskScaleComments
     *
     * @return SoaScaleSuperClass
     */
    public function setOperationalRiskScaleComments($soaScaleComments): self
    {
        $this->soaScaleComments = $soaScaleComments;

        return $this;
    }
}
