<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Amv
 *
 * @ORM\Table(name="amvs", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="asset", columns={"asset_id"}),
 *      @ORM\Index(name="threat", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability", columns={"vulnerability_id"}),
 *      @ORM\Index(name="measure1", columns={"measure1_id"}),
 *      @ORM\Index(name="measure2", columns={"measure2_id"}),
 *      @ORM\Index(name="measure3", columns={"measure3_id"})
 * })
 * @ORM\MappedSuperclass
 */
class AmvSuperclass extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var \MonarcCore\Model\Entity\Asset
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var \MonarcCore\Model\Entity\Threat
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Threat", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $threat;

    /**
     * @var \MonarcCore\Model\Entity\Vulnerability
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Vulnerability", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $vulnerability;

    /**
     * @var \MonarcCore\Model\Entity\Measure
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="measure1_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $measure1;

    /**
     * @var \MonarcCore\Model\Entity\Measure
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="measure2_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $measure2;

    /**
     * @var \MonarcCore\Model\Entity\Measure
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Measure", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="measure3_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $measure3;

    /**
     * @var smallint
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = '1';

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = '1';

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
     * @return int
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param int $anr
     * @return Amv
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return Threat
     */
    public function getThreat()
    {
        return $this->threat;
    }

    /**
     * @param Threat $threat
     * @return Amv
     */
    public function setThreat($threat)
    {
        $this->threat = $threat;
        return $this;
    }

    /**
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     * @return Amv
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @return Vulnerability
     */
    public function getVulnerability()
    {
        return $this->vulnerability;
    }

    /**
     * @param Vulnerability $vulnerability
     * @return Amv
     */
    public function setVulnerability($vulnerability)
    {
        $this->vulnerability = $vulnerability;
        return $this;
    }

    /**
     * @return Measure
     */
    public function getMeasure1()
    {
        return $this->measure1;
    }

    /**
     * @param Measure $measure1
     * @return Amv
     */
    public function setMeasure1($measure1)
    {
        $this->measure1 = $measure1;
        return $this;
    }

    /**
     * @return Measure
     */
    public function getMeasure2()
    {
        return $this->measure2;
    }

    /**
     * @param Measure $measure2
     * @return Amv
     */
    public function setMeasure2($measure2)
    {
        $this->measure2 = $measure2;
        return $this;
    }

    /**
     * @return Measure
     */
    public function getMeasure3()
    {
        return $this->measure3;
    }

    /**
     * @param Measure $measure3
     * @return Amv
     */
    public function setMeasure3($measure3)
    {
        $this->measure3 = $measure3;
        return $this;
    }

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'asset',
        ),
    );

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $texts = ['vulnerability', 'asset'];

            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ($partial) ? false : true,
                    'allow_empty' => false,
                    'filters' => array(
                        array(
                            'name' => 'Digits',
                        ),
                    ),
                    'validators' => array(),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'threat',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'filters' => array(
                    array(
                        'name' => 'Digits',
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',//'\MonarcCore\Validator\UniqueAMV',
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\Callback::INVALID_VALUE => 'This AMV link is already used',
                            ),
                            'callback' => function ($value, $context = array()) use ($partial) {
                                if (!$partial) {
                                    $adapter = $this->getDbAdapter();
                                    if (empty($adapter)) {
                                        return false;
                                    } else {
                                        $res = $adapter->getRepository(get_class($this))->createQueryBuilder('a')
                                            ->select(array('a.id'))
                                            ->where(' a.vulnerability = :vulnerability ')
                                            ->andWhere(' a.asset = :asset ')
                                            ->andWhere(' a.threat = :threat ')
                                            ->setParameter(':vulnerability', $context['vulnerability'])
                                            ->setParameter(':threat', $context['threat'])
                                            ->setParameter(':asset', $context['asset']);
                                        if (empty($context['anr'])) {
                                            $res = $res->andWhere(' a.anr IS NULL ');
                                        } else {
                                            $res = $res->andWhere(' a.anr = :anr ')
                                                ->setParameter(':anr', $context['anr']);
                                        }
                                        $res = $res->getQuery()
                                            ->getResult();
                                        $context['id'] = empty($context['id']) ? $this->get('id') : $context['id'];
                                        if (!empty($res) && $context['id'] != $res[0]['id']) {
                                            return false;
                                        }
                                    }
                                    return true;
                                } else {
                                    return true;
                                }
                            },
                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }
}

