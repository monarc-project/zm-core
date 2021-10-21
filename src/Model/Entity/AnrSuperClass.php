<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Monarc\Core\Model\Entity\Traits;

/**
 * Anr
 *
 * @ORM\Table(name="anrs")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class AnrSuperClass extends AbstractEntity
{
    use Traits\CreateEntityTrait;
    use Traits\UpdateEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ArrayCollection|ObjectSuperClass[]
     *
     * @ORM\ManyToMany(targetEntity="MonarcObject", mappedBy="anrs")
     */
    protected $objects;

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
     * @ORM\Column(name="description1", type="text", nullable=true)
     */
    protected $description1;

    /**
     * @var string
     *
     * @ORM\Column(name="description2", type="text", nullable=true)
     */
    protected $description2;

    /**
     * @var string
     *
     * @ORM\Column(name="description3", type="text", nullable=true)
     */
    protected $description3;

    /**
     * @var string
     *
     * @ORM\Column(name="description4", type="text", nullable=true)
     */
    protected $description4;

    /**
     * @var int
     *
     * @ORM\Column(name="seuil1", type="integer", options={"unsigned":true, "default":4})
     */
    protected $seuil1 = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="seuil2", type="integer", options={"unsigned":true, "default":8})
     */
    protected $seuil2 = 8;

    /**
     * @var int
     *
     * @ORM\Column(name="seuil_rolf1", type="integer", options={"unsigned":true, "default":4})
     */
    protected $seuilRolf1 = 4;

    /**
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
    protected $seuilTraitement;

    /**
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

    public function getId(): int
    {
        return $this->id;
    }

    // TODO: remove the hack when AnrService is refactored.
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return ArrayCollection|ObjectSuperClass[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @param ObjectSuperClass[] $objects
     * @return Anr
     */
    public function setObjects($objects): self
    {
        $this->objects = $objects;

        return $this;
    }

    public function getSeuil1(): int
    {
        return $this->seuil1;
    }

    public function getSeuil2(): int
    {
        return $this->seuil2;
    }

    public function getSeuilRolf1(): int
    {
        return $this->seuilRolf1;
    }

    public function getSeuilRolf2(): int
    {
        return $this->seuilRolf2;
    }

    public function getShowRolfBrut(): int
    {
        return $this->showRolfBrut;
    }

    public function getLabelByLanguageIndex(int $languageIndex): string
    {
        if (!\in_array($languageIndex, range(1, 4), true)) {
            return '';
        }

        return (string)$this->{'label' . $languageIndex};
    }

    /**
     * On BO side there is no language field in the Anr entity, so user's language is used.
     * On FO side there is a language field, so Anr's lang is used.
     *
     * @return string
     */
    public function getLabel(): string
    {
        $languageNumber = $this->getLanguage();
        if (!property_exists(\get_class($this), 'label' . $languageNumber)) {
            throw new LogicException(sprintf('Language number %d does not exist.', $languageNumber));
        }

        return (string)$this->{'label' . $languageNumber};
    }
}
