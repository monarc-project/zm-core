<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits;

/**
 * @ORM\Table(name="anrs")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class AnrSuperClass
{
    use Traits\CreateEntityTrait;
    use Traits\UpdateEntityTrait;

    public const STATUS_ACTIVE = 1;
    public const STATUS_AWAITING_OF_IMPORT = 2;
    public const STATUS_UNDER_IMPORT = 3;
    public const STATUS_IMPORT_ERROR = 9;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ArrayCollection|InstanceSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="Instance", mappedBy="anr")
     */
    protected $instances;

    /**
     * @var AnrInstanceMetadataFieldSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AnrInstanceMetadataField", mappedBy="anr")
     */
    protected $anrInstanceMetadataFields;

    /**
     * Informational risks min threshold setting.
     *
     * @var int
     *
     * @ORM\Column(name="seuil1", type="integer", options={"unsigned":true, "default":4})
     */
    protected $seuil1 = 4;

    /**
     * Informational risks max threshold setting.
     *
     * @var int
     *
     * @ORM\Column(name="seuil2", type="integer", options={"unsigned":true, "default":8})
     */
    protected $seuil2 = 8;

    /**
     * Operational risks min threshold setting.
     *
     * @var int
     *
     * @ORM\Column(name="seuil_rolf1", type="integer", options={"unsigned":true, "default":4})
     */
    protected $seuilRolf1 = 4;

    /**
     * Operational risks max threshold setting.
     *
     * @var int
     *
     * @ORM\Column(name="seuil_rolf2", type="integer", options={"unsigned":true, "default":8})
     */
    protected $seuilRolf2 = 8;

    /**
     * @var int
     *
     * @ORM\Column(name="seuil_traitement", type="integer", options={"unsigned":true})
     */
    protected $seuilTraitement = 0;

    /**
     * Used to mark different steps initialisation/completion (applied to all $init.., $eval... properties).
     *
     * @var int
     *
     * @ORM\Column(name="init_anr_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initAnrContext = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="init_eval_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initEvalContext = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="init_risk_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initRiskContext = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="init_def_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initDefContext = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="init_livrable_done", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initLivrableDone = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="model_summary", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $modelSummary = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="model_livrable_done", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $modelLivrableDone = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="eval_risks", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $evalRisks = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="eval_plan_risks", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $evalPlanRisks = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="eval_livrable_done", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $evalLivrableDone = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="manage_risks", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $manageRisks = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="context_ana_risk", type="text", nullable=true)
     */
    protected $contextAnaRisk;

    /**
     * @var string
     *
     * @ORM\Column(name="context_gest_risk", type="text", nullable=true)
     */
    protected $contextGestRisk;

    /**
     * @var string
     *
     * @ORM\Column(name="synth_threat", type="text", nullable=true)
     */
    protected $synthThreat;

    /**
     * @var string
     *
     * @ORM\Column(name="synth_act", type="text", nullable=true)
     */
    protected $synthAct;

    /**
     * @var int
     *
     * @ORM\Column(name="cache_model_show_rolf_brut", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $cacheModelShowRolfBrut = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="show_rolf_brut", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $showRolfBrut = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=false, options={"unsigned":true})
     */
    protected $status = self::STATUS_ACTIVE;

    public function __construct()
    {
        $this->anrInstanceMetadataFields = new ArrayCollection();
        $this->instances = new ArrayCollection();
    }

    /**
     * Only the primitive data types properties values are set to the new object.
     * The relation properties have to be recreated manually.
     */
    public static function constructFromObject(AnrSuperClass $anr): AnrSuperClass
    {
        return (new static())
            ->setSeuil1($anr->getSeuil1())
            ->setSeuil2($anr->getSeuil2())
            ->setSeuilRolf1($anr->getSeuilRolf1())
            ->setSeuilRolf2($anr->getSeuilRolf2())
            ->setSeuilTraitement($anr->getSeuilTraitement())
            ->setShowRolfBrut($anr->showRolfBrut())
            ->setInitAnrContext($anr->getInitAnrContext())
            ->setInitEvalContext($anr->getInitEvalContext())
            ->setInitDefContext($anr->getInitDefContext())
            ->setInitRiskContext($anr->getInitRiskContext())
            ->setInitLivrableDone($anr->getInitLivrableDone())
            ->setEvalRisks($anr->getEvalRisks())
            ->setEvalPlanRisks($anr->getEvalPlanRisks())
            ->setEvalLivrableDone($anr->getEvalLivrableDone())
            ->setContextAnaRisk($anr->getContextAnaRisk())
            ->setContextGestRisk($anr->getContextGestRisk())
            ->setManageRisks($anr->getManageRisks())
            ->setModelSummary($anr->getModelSummary())
            ->setModelLivrableDone($anr->getModelLivrableDone())
            ->setSynthAct($anr->getSynthAct())
            ->setSynthThreat($anr->getSynthThreat())
            ->setCacheModelShowRolfBrut($anr->getCacheModelShowRolfBrut());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getInstances()
    {
        return $this->instances;
    }

    public function addInstance(InstanceSuperClass $instance): self
    {
        if (!$this->instances->contains($instance)) {
            $this->instances->add($instance);
            $instance->setAnr($this);
        }

        return $this;
    }

    public function getAnrInstanceMetadataFields()
    {
        return $this->anrInstanceMetadataFields;
    }

    public function addAnrInstanceMetadataField(AnrInstanceMetadataFieldSuperClass $instanceMetadataField): self
    {
        if (!$this->anrInstanceMetadataFields->contains($instanceMetadataField)) {
            $this->anrInstanceMetadataFields->add($instanceMetadataField);
            $instanceMetadataField->setAnr($this);
        }

        return $this;
    }

    public function setSeuil1(int $seuil1): self
    {
        $this->seuil1 = $seuil1;

        return $this;
    }

    public function getSeuil1(): int
    {
        return $this->seuil1;
    }

    public function setSeuil2(int $seuil2): self
    {
        $this->seuil2 = $seuil2;

        return $this;
    }
    public function getSeuil2(): int
    {
        return $this->seuil2;
    }

    public function setSeuilRolf1(int $seuilRolf1): self
    {
        $this->seuilRolf1 = $seuilRolf1;

        return $this;
    }

    public function getSeuilRolf1(): int
    {
        return $this->seuilRolf1;
    }

    public function setSeuilRolf2(int $seuilRolf2): self
    {
        $this->seuilRolf2 = $seuilRolf2;

        return $this;
    }

    public function getSeuilRolf2(): int
    {
        return $this->seuilRolf2;
    }

    public function setSeuilTraitement(int $seuilTraitement): self
    {
        $this->seuilTraitement = $seuilTraitement;

        return $this;
    }

    public function getSeuilTraitement(): int
    {
        return (int)$this->seuilTraitement;
    }

    public function setShowRolfBrut(bool $showRolfBrut): self
    {
        $this->showRolfBrut = (int)$showRolfBrut;

        return $this;
    }

    public function showRolfBrut(): bool
    {
        return (bool)$this->showRolfBrut;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusName(): string
    {
        switch ($this->status) {
            case self::STATUS_AWAITING_OF_IMPORT:
                return 'awaiting for import';
            case self::STATUS_UNDER_IMPORT:
                return 'under import';
            case self::STATUS_IMPORT_ERROR:
                return 'import error';
            case self::STATUS_ACTIVE:
            default:
                return 'active';
        }
    }

    public function isActive(): bool
    {
        return $this->status === static::STATUS_ACTIVE;
    }

    public function getInitAnrContext(): int
    {
        return $this->initAnrContext;
    }

    public function setInitAnrContext(int $initAnrContext): self
    {
        $this->initAnrContext = $initAnrContext;

        return $this;
    }

    public function getInitEvalContext(): int
    {
        return $this->initEvalContext;
    }

    public function setInitEvalContext(int $initEvalContext): self
    {
        $this->initEvalContext = $initEvalContext;

        return $this;
    }

    public function getInitRiskContext(): int
    {
        return $this->initRiskContext;
    }

    public function setInitRiskContext(int $initRiskContext): self
    {
        $this->initRiskContext = $initRiskContext;

        return $this;
    }

    public function getInitDefContext(): int
    {
        return $this->initDefContext;
    }

    public function setInitDefContext(int $initDefContext): self
    {
        $this->initDefContext = $initDefContext;

        return $this;
    }

    public function getInitLivrableDone(): int
    {
        return $this->initLivrableDone;
    }

    public function setInitLivrableDone(int $initLivrableDone): self
    {
        $this->initLivrableDone = $initLivrableDone;

        return $this;
    }

    public function getModelSummary(): int
    {
        return $this->modelSummary;
    }

    public function setModelSummary(int $modelSummary): self
    {
        $this->modelSummary = $modelSummary;

        return $this;
    }

    public function getModelLivrableDone(): int
    {
        return $this->modelLivrableDone;
    }

    public function setModelLivrableDone(int $modelLivrableDone): self
    {
        $this->modelLivrableDone = $modelLivrableDone;

        return $this;
    }

    public function getEvalRisks(): int
    {
        return $this->evalRisks;
    }

    public function setEvalRisks(int $evalRisks): self
    {
        $this->evalRisks = $evalRisks;

        return $this;
    }

    public function getEvalPlanRisks(): int
    {
        return $this->evalPlanRisks;
    }

    public function setEvalPlanRisks(int $evalPlanRisks): self
    {
        $this->evalPlanRisks = $evalPlanRisks;

        return $this;
    }

    public function getEvalLivrableDone(): int
    {
        return $this->evalLivrableDone;
    }

    public function setEvalLivrableDone(int $evalLivrableDone): self
    {
        $this->evalLivrableDone = $evalLivrableDone;

        return $this;
    }

    public function getManageRisks(): int
    {
        return $this->manageRisks;
    }

    public function setManageRisks(int $manageRisks): self
    {
        $this->manageRisks = $manageRisks;

        return $this;
    }

    public function getContextAnaRisk(): string
    {
        return $this->contextAnaRisk;
    }

    public function setContextAnaRisk(string $contextAnaRisk): self
    {
        $this->contextAnaRisk = $contextAnaRisk;

        return $this;
    }

    public function getContextGestRisk(): string
    {
        return $this->contextGestRisk;
    }

    public function setContextGestRisk(string $contextGestRisk): self
    {
        $this->contextGestRisk = $contextGestRisk;

        return $this;
    }

    public function getSynthThreat(): string
    {
        return $this->synthThreat;
    }

    public function setSynthThreat(string $synthThreat): self
    {
        $this->synthThreat = $synthThreat;

        return $this;
    }

    public function getSynthAct(): string
    {
        return $this->synthAct;
    }

    public function setSynthAct(string $synthAct): self
    {
        $this->synthAct = $synthAct;

        return $this;
    }

    public function getCacheModelShowRolfBrut(): bool
    {
        return (bool)$this->cacheModelShowRolfBrut;
    }

    public function setCacheModelShowRolfBrut(bool $cacheModelShowRolfBrut): self
    {
        $this->cacheModelShowRolfBrut = (int)$cacheModelShowRolfBrut;

        return $this;
    }
}
