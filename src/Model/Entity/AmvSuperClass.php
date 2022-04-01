<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;
use Monarc\Core\Model\Entity\Traits\UpdateEntityTrait;
use Laminas\Validator\Uuid as ValidatorUuid;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Uuid;

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
 * @ORM\HasLifecycleCallbacks()
 */
class AmvSuperClass extends AbstractEntity
{
    use CreateEntityTrait;
    use UpdateEntityTrait;

    /**
     * @var LazyUuidFromString|string
     *
     * @ORM\Column(name="uuid", type="uuid", nullable=false)
     * @ORM\Id
     */
    protected $uuid;

    /**
     * @var AnrSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Anr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="anr_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    protected $anr;

    /**
     * @var AssetSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Asset", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $asset;

    /**
     * @var ThreatSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Threat", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="threat_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $threat;

    /**
     * @var VulnerabilitySuperClass
     *
     * @ORM\ManyToOne(targetEntity="Vulnerability", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="vulnerability_id", referencedColumnName="uuid", nullable=true)
     * })
     */
    protected $vulnerability;

    /**
     * @var ArrayCollection|MeasureSuperClass[]
     * @ORM\ManyToMany(targetEntity="Measure", mappedBy="amvs", cascade={"persist"})
     */
    protected $measures;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $position = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":1})
     */
    protected $status = 1;

    protected $parameters = array(
        'implicitPosition' => array(
            'field' => 'asset',
        ),
    );

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return self
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return AnrSuperClass
     */
    public function getAnr()
    {
        return $this->anr;
    }

    /**
     * @param AnrSuperClass $anr
     */
    public function setAnr($anr)
    {
        $this->anr = $anr;
        return $this;
    }

    /**
     * @return ThreatSuperClass
     */
    public function getThreat()
    {
        return $this->threat;
    }

    /**
     * @param ThreatSuperClass $threat
     */
    public function setThreat($threat)
    {
        $this->threat = $threat;
        return $this;
    }

    /**
     * @return AssetSuperClass
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param AssetSuperClass $asset
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
        return $this;
    }

    /**
     * @return VulnerabilitySuperClass
     */
    public function getVulnerability()
    {
        return $this->vulnerability;
    }

    /**
     * @param VulnerabilitySuperClass $vulnerability
     */
    public function setVulnerability($vulnerability)
    {
        $this->vulnerability = $vulnerability;
        return $this;
    }

    /**
     * @return MeasureSuperClass[]
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * @param MeasureSuperClass[] $measures
     */
    public function setMeasures($measures): self
    {
        // TODO: change when AnrService will be refactored.
        if ($measures === null) {
            $this->measures = new ArrayCollection();
        } else {
            foreach ($measures as $measure) {
                $this->addMeasure($measure);
            }
        }

        return $this;
    }

    public function addMeasure(MeasureSuperClass $measure): self
    {
        // TODO: move to the constructor, after Anr duplication is refactored.
        if ($this->measures === null) {
            $this->measures = new ArrayCollection();
        }

        if (!$this->measures->contains($measure)) {
            $this->measures->add($measure);

            $measure->addAmv($this);
        }

        return $this;
    }

    public function removeMeasure(MeasureSuperClass $measure): self
    {
        if ($this->measures->contains($measure)) {
            $this->measures->removeElement($measure);
            $measure->removeAmv($this);
        }

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): AmvSuperClass
    {
        $this->position = $position;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): AmvSuperClass
    {
        $this->status = $status;

        return $this;
    }

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
        return [$filterJoin, [], $filtersCol];
    }

    /**
     * TODO: Remove the business logic from the entity.
     * It brakes the responsibility principles and hide the logic.
     */
    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            parent::getInputFilter($partial);

            $this->inputFilter->add(array(
                'name' => 'uuid',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'Callback', //'Monarc\Core\Validator\Uuid',
                        'options' => array(
                            'messages' => array(
                                \Laminas\Validator\Callback::INVALID_VALUE => 'an uuid is missing or incorrect',
                            ),
                            'callback' => function ($value, $context = array()) use ($partial) {
                                if (!$partial) {
                                    if (!preg_match(ValidatorUuid::REGEX_UUID, $context['uuid'])) {
                                        return false;
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

            $texts = ['threat', 'vulnerability', 'asset'];
            foreach ($texts as $text) {
                $this->inputFilter->add(array(
                    'name' => $text,
                    'required' => ($partial) ? false : true,
                    'allow_empty' => false,
                    'validators' => array(
                        array(
                            'name' => 'Callback', //'Monarc\Core\Validator\Uuid',
                            'options' => array(
                                'messages' => array(
                                    \Laminas\Validator\Callback::INVALID_VALUE => 'an uuid is missing or incorrect',
                                ),
                                'callback' => function ($value, $context = array()) use ($partial) {
                                    if (!$partial) {
                                        return Uuid::isValid(\is_array($value) ? $value['uuid'] : $value);

                                    }

                                    return true;
                                },
                            ),
                        ),
                    ),
                ));
            }

            $this->inputFilter->add(array(
                'name' => 'threat',
                'required' => ($partial) ? false : true,
                'allow_empty' => false,
                'validators' => array(
                    array(
                        'name' => 'Callback', //'Monarc\Core\Validator\UniqueAMV',
                        'options' => array(
                            'messages' => array(
                                \Laminas\Validator\Callback::INVALID_VALUE => 'This AMV link is already used',
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
                                                ->setParameter(':vulnerability', !is_array($context['vulnerability'])?$context['vulnerability']:$context['vulnerability']['uuid'])
                                                ->setParameter(':threat', !is_array($context['threat'])?$context['threat']:$context['threat']['uuid'])
                                                ->setParameter(':asset', !is_array($context['asset'])?$context['asset']:$context['asset']['uuid'])
                                                ->setParameter(':anr', $context['anr']);
                                        }
                                        $res = $res->getQuery()
                                            ->getResult();
                                        $amvUuid = empty($context['uuid']) ? $this->getUuid() : $context['uuid'];
                                        if(empty($context['uuid'])){ //creation
                                            if (!empty($res) && $amvUuid != $res[0]['uuid']) {
                                                return false;
                                            }
                                        }else { //edition
                                            if (!empty($res) && $amvUuid != $res[0]['uuid']) { //the amv link is existing
                                                return false;
                                            }
                                        }
                                    }
                                    return true;
                                }

                                return true;
                            },
                        ),
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }
}
