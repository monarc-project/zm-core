<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;

/**
 * @ORM\Table(name="instances_consequences", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"}),
 *      @ORM\Index(name="scale_impact_type_id", columns={"scale_impact_type_id"})
 * })
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class InstanceConsequenceSuperClass
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
     * @var InstanceSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $instance;

    /**
     * @var ScaleImpactTypeSuperClass
     *
     * @ORM\ManyToOne(targetEntity="ScaleImpactType", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scale_impact_type_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $scaleImpactType;

    /**
     * @var int
     *
     * @ORM\Column(name="is_hidden", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $isHidden = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="c", type="smallint", options={"unsigned":true, "default":-1})
     */
    protected $c = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="i", type="smallint", options={"unsigned":true, "default":-1})
     */
    protected $i = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="d", type="smallint", options={"unsigned":true, "default":-1})
     */
    protected $d = -1;

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

    public function getInstance(): InstanceSuperClass
    {
        return $this->instance;
    }

    public function setInstance(InstanceSuperClass $instance): self
    {
        $this->instance = $instance;
        $this->instance->addInstanceConsequence($this);

        return $this;
    }

    public function getScaleImpactType(): ScaleImpactTypeSuperClass
    {
        return $this->scaleImpactType;
    }

    public function setScaleImpactType(ScaleImpactTypeSuperClass $scaleImpactType): self
    {
        $this->scaleImpactType = $scaleImpactType;

        return $this;
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

    public function setAvailability(int $d): self
    {
        $this->d = $d;

        return $this;
    }

    public function getAvailability(): int
    {
        return $this->d;
    }

    public static function getAvailableScalesCriteria(): array
    {
        return [
            'c' => 'Confidentiality',
            'i' => 'Integrity',
            'd' => 'Availability'
        ];
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
