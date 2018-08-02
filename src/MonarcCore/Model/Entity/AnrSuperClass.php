<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Anr
 *
 * @ORM\Table(name="anrs")
 * @ORM\MappedSuperclass
 */
class AnrSuperClass extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Object
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Object", mappedBy="anrs")
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
     * @var text
     *
     * @ORM\Column(name="description1", type="text", length=255, nullable=true)
     */
    protected $description1;

    /**
     * @var text
     *
     * @ORM\Column(name="description2", type="text", length=255, nullable=true)
     */
    protected $description2;

    /**
     * @var text
     *
     * @ORM\Column(name="description3", type="text", length=255, nullable=true)
     */
    protected $description3;

    /**
     * @var text
     *
     * @ORM\Column(name="description4", type="text", length=255, nullable=true)
     */
    protected $description4;

    /**
     * @var integer
     *
     * @ORM\Column(name="seuil1", type="integer", options={"unsigned":true, "default":4})
     */
    protected $seuil1 = 4;

    /**
     * @var integer
     *
     * @ORM\Column(name="seuil2", type="integer", options={"unsigned":true, "default":8})
     */
    protected $seuil2 = 8;

    /**
     * @var integer
     *
     * @ORM\Column(name="seuil_rolf1", type="integer", options={"unsigned":true, "default":4})
     */
    protected $seuilRolf1 = 4;

    /**
     * @var integer
     *
     * @ORM\Column(name="seuil_rolf2", type="integer", options={"unsigned":true, "default":8})
     */
    protected $seuilRolf2 = 8;

    /**
     * @var integer
     *
     * @ORM\Column(name="seuil_traitement", type="integer", options={"unsigned":true})
     */
    protected $seuilTraitement;

    /**
     * @var smallint
     *
     * @ORM\Column(name="init_anr_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initAnrContext = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="init_eval_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initEvalContext = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="init_risk_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initRiskContext = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="init_def_context", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initDefContext = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="init_livrable_done", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $initLivrableDone = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="model_summary", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $modelSummary = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="model_livrable_done", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $modelLivrableDone = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="eval_risks", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $evalRisks = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="eval_plan_risks", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $evalPlanRisks = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="eval_livrable_done", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $evalLivrableDone = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="manage_risks", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $manageRisks = '0';


    /**
     * @var text
     *
     * @ORM\Column(name="context_ana_risk", type="text", length=255, nullable=true)
     */
    protected $contextAnaRisk;

    /**
     * @var text
     *
     * @ORM\Column(name="context_gest_risk", type="text", length=255, nullable=true)
     */
    protected $contextGestRisk;

    /**
     * @var text
     *
     * @ORM\Column(name="synth_threat", type="text", length=255, nullable=true)
     */
    protected $synthThreat;

    /**
     * @var text
     *
     * @ORM\Column(name="synth_act", type="text", length=255, nullable=true)
     */
    protected $synthAct;

    /**
     * @var smallint
     *
     * @ORM\Column(name="cache_model_show_rolf_brut", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $cacheModelShowRolfBrut = '0';

    /**
     * @var smallint
     *
     * @ORM\Column(name="show_rolf_brut", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $showRolfBrut = '0';

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
     * @return Model
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Object
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @param Object $objects
     * @return Anr
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;
        return $this;
    }
}
