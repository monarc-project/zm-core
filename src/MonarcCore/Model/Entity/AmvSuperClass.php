<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zend\Validator\Uuid;

/**
 * Amv
 *
 * @ORM\Table(name="amvs", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="asset", columns={"asset_id"}),
 *      @ORM\Index(name="threat", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability", columns={"vulnerability_id"}),
 * })
 * @ORM\MappedSuperclass
 */
class AmvSuperclass extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var \MonarcCore\Model\Entity\Anr
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Anr", )
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
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var \MonarcCore\Model\Entity\Threat
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Threat", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $threat;

    /**
     * @var \MonarcCore\Model\Entity\Vulnerability
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Vulnerability", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $vulnerability;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Measure", mappedBy="amvs", cascade={"persist"})
     * @ORM\JoinTable(name="measures_amvs",
     *  joinColumns={@ORM\JoinColumn(name="amv_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="measure_id", referencedColumnName="uuid")}
     * )
     */
    protected $measures;

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
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param int $id
     * @return Model
     */
    public function setUuid($id)
    {
        $this->uuid = $id;
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
     * @return Measures
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * @param Measure $measures
     * @return Amv
     */
    public function setMeasures($measures)
    {
        $this->measures = $measures;
        return $this;
    }

    /**
     * @param Measure $measure
     * @return Amv
     */
    public function addMeasure($measure)
    {
        $this->measures[] = $measure;
        return $this;
    }

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'asset',
        ),
    );


    public function getFiltersForService()
    {
        $filterJoin = [
            [
                'as' => 'a',
                'rel' => 'asset',
            ],
            [
                'as' => 'th',
                'rel' => 'threat',
            ],
            [
                'as' => 'v',
                'rel' => 'vulnerability',
            ],
        ];
        $filterLeft = [
            // [
            //     'as' => 'm1',
            //     'rel' => 'measure1',
            // ],
            // [
            //     'as' => 'm2',
            //     'rel' => 'measure2',
            // ],
            // [
            //     'as' => 'm3',
            //     'rel' => 'measure3',
            // ],
        ];
        $filtersCol = [
            'a.code',
            'a.label1',
            'a.label2',
            'a.label3',
            'a.description1',
            'a.description2',
            'a.description3',
            'th.code',
            'th.label1',
            'th.label2',
            'th.label3',
            'th.description1',
            'th.description2',
            'th.description3',
            'v.code',
            'v.label1',
            'v.label2',
            'v.label3',
            'v.description1',
            'v.description2',
            'v.description3',
        ];
        return [$filterJoin, $filterLeft, $filtersCol];
    }

    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'status',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'validators' => array(
                    array(
                        'name' => 'Callback', //'\MonarcCore\Validator\Uuid',
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\Callback::INVALID_VALUE => 'an uuid is missing or incorrect',
                            ),
                            'callback' => function ($value, $context = array()) use ($partial) {
                                if (!$partial) {
                                    $texts = ['threat', 'vulnerability', 'asset'];
                                    foreach ($texts as $text) {
                                        if (is_array($context[$text])) {
                                            if (!preg_match(Uuid::REGEX_UUID, $context[$text]['uuid'])) return false;
                                        } else {
                                            if (!preg_match(Uuid::REGEX_UUID, $context[$text])) return false;
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

            $this->inputFilter->add(array(
                'name' => 'threat',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                // 'filters' => array(
                //     array(
                //         'name' => 'Digits',
                //     ),
                // ),
                'validators' => array(
                    array(
                        'name' => 'Callback', //'\MonarcCore\Validator\UniqueAMV',
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\Callback::INVALID_VALUE => 'This AMV link is already used',
                            ),
                            'callback' => function ($value, $context = array()) use ($partial) {
                                if (!$partial) {
                                    $adapter = $this->getDbAdapter();
                                    if (empty($adapter)) {
                                        return false;
                                    } else { //BO case
                                        $res = $adapter->getRepository(get_class($this))->createQueryBuilder('a')
                                            ->select(array('a.uuid'));
                                        if (empty($context['anr'])) {
                                            $res->innerJoin('a.vulnerability', 'vulnerability')
                                                ->innerJoin('a.threat', 'threat')
                                                ->innerJoin('a.asset', 'asset')
                                                ->where('vulnerability.uuid = :vulnerability')
                                                ->andWhere('asset.uuid = :asset')
                                                ->andWhere('threat.uuid = :threat')
                                                ->andWhere('vulnerability.anr IS NULL')
                                                ->andWhere('asset.anr IS NULL')
                                                ->andWhere('threat.anr IS NULL ')
                                                ->andWhere(' a.anr  IS NULL')
                                                ->setParameter(':vulnerability', $context['vulnerability'])
                                                ->setParameter(':threat', $context['threat'])
                                                ->setParameter(':asset', $context['asset']);
                                        } else { //FO case
                                            $res->innerJoin('a.vulnerability', 'vulnerability')
                                                ->innerJoin('a.threat', 'threat')
                                                ->innerJoin('a.asset', 'asset')
                                                ->where('vulnerability.uuid = :vulnerability ')
                                                ->andWhere('asset.uuid = :asset ')
                                                ->andWhere('threat.uuid = :threat ')
                                                ->andWhere('vulnerability.anr = :anr ')
                                                ->andWhere('asset.anr = :anr ')
                                                ->andWhere('threat.anr = :anr ')
                                                ->andWhere(' a.anr = :anr ')
                                                ->setParameter(':vulnerability', $context['vulnerability']['uuid'])
                                                ->setParameter(':threat', $context['threat']['uuid'])
                                                ->setParameter(':asset', $context['asset']['uuid'])
                                                ->setParameter(':anr', $context['anr']);
                                        }
                                        $res = $res->getQuery()
                                            ->getResult();
                                        $context['uuid'] = empty($context['uuid']) ? $this->get('uuid') : $context['uuid'];
                                        if (!empty($res) && $context['uuid'] != $res[0]['uuid']) {
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
