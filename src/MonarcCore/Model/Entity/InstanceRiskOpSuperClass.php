<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Instance Risk Op
 *
 * @ORM\Table(name="instances_risks_op", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"}),
 *      @ORM\Index(name="instance_id", columns={"instance_id"}),
 *      @ORM\Index(name="object_id", columns={"object_id"}),
 *      @ORM\Index(name="rolf_risk_id", columns={"rolf_risk_id"})
 * })
 * @ORM\MappedSuperclass
 */
class InstanceRiskOpSuperClass extends AbstractEntity
{
    const KIND_REDUCTION = 1;
    const KIND_REFUS = 2;
    const KIND_ACCEPTATION = 3;
    const KIND_PARTAGE = 4;
    const KIND_NOT_TREATED = 5;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var \MonarcCore\Model\Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Instance", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $instance;

    /**
     * @var \MonarcCore\Model\Entity\Object
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Object", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $object;

    /**
     * @var \MonarcCore\Model\Entity\RolfRisk
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\RolfRisk", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rolf_risk_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $rolfRisk;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_code", type="string", length=255, nullable=true)
     */
    protected $riskCacheCode;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label1", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel1;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label2", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel2;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label3", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel3;

    /**
     * @var string
     *
     * @ORM\Column(name="risk_cache_label4", type="string", length=255, nullable=true)
     */
    protected $riskCacheLabel4;

    /**
     * @var text
     *
     * @ORM\Column(name="risk_cache_description1", type="text", length=255, nullable=true)
     */
    protected $riskCacheDescription1;

    /**
     * @var text
     *
     * @ORM\Column(name="risk_cache_description2", type="text", length=255, nullable=true)
     */
    protected $riskCacheDescription2;

    /**
     * @var text
     *
     * @ORM\Column(name="risk_cache_description3", type="text", length=255, nullable=true)
     */
    protected $riskCacheDescription3;

    /**
     * @var text
     *
     * @ORM\Column(name="risk_cache_description4", type="text", length=255, nullable=true)
     */
    protected $riskCacheDescription4;

    /**
     * @var smallint
     *
     * @ORM\Column(name="brut_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutProb = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="brut_r", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutR = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="brut_o", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutO = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="brut_l", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutL = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="brut_f", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutF = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="brut_p", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $brutP = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="cache_brut_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheBrutRisk = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="net_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netProb = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="net_r", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netR = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="net_o", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netO = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="net_l", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netL = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="net_f", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netF = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="net_p", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $netP = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="cache_net_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheNetRisk = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="targeted_prob", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedProb = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="targeted_r", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedR = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="targeted_o", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedO = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="targeted_l", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedL = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="targeted_f", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedF = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="targeted_p", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $targetedP = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="cache_targeted_risk", type="smallint", options={"unsigned":false, "default":-1})
     */
    protected $cacheTargetedRisk = '-1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="kind_of_measure", type="smallint", options={"unsigned":true, "default":5})
     */
    protected $kindOfMeasure = 5;

    /**
     * @var text
     *
     * @ORM\Column(name="comment", type="text", length=255, nullable=true)
     */
    protected $comment;

    /**
     * @var text
     *
     * @ORM\Column(name="mitigation", type="text", length=255, nullable=true)
     */
    protected $mitigation;

    /**
     * @var smallint
     *
     * @ORM\Column(name="`specific`", type="smallint", options={"unsigned":true, "default":0})
     */
    protected $specific = '0';

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
     * @return Instance
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param int $anr
     * @return Instance
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return Instance
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param Instance $instance
     * @return InstanceRiskOp
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return Object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Object $object
     * @return InstanceRiskOp
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return RolfRisk
     */
    public function getRolfRisk()
    {
        return $this->rolfRisk;
    }

    /**
     * @param RolfRisk $rolfRisk
     * @return InstanceRiskOp
     */
    public function setRolfRisk($rolfRisk)
    {
        $this->rolfRisk = $rolfRisk;
        return $this;
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $integers = [
                'brutProb',
                'brutR',
                'brutO',
                'brutL',
                'brutF',
                'brutP',
                'cacheBrutRisk',
                'netProb',
                'netR',
                'netO',
                'netL',
                'netF',
                'netP',
                'cacheNetRisk',
                'targetedProb',
                'targetedR',
                'targetedO',
                'targetedL',
                'targetedF',
                'targetedP',
                'cacheTargetedRisk',
                'kindOfMeasure',
                'specific',
            ];
            foreach ($integers as $i) {
                $this->inputFilter->add(array(
                    'name' => $i,
                    'required' => false,
                    'allow_empty' => false,
                    'filters' => array(),
                    'validators' => array(
                        array(
                            'name' => 'IsInt',
                        ),
                    ),
                ));
            }
        }
        return $this->inputFilter;
    }
}

