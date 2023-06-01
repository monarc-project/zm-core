<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\RolfRiskSuperClass;
use Monarc\Core\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\MeasureTable;
use Monarc\Core\Model\Table\RolfRiskTable;
use Monarc\Core\Model\Table\RolfTagTable;
use Monarc\Core\Table\InstanceTable;
use Monarc\Core\Table\MonarcObjectTable;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping\MappingException;

/**
 * Rolf Risk Service
 *
 * Class RolfRiskService
 * @package Monarc\Core\Service
 */
class RolfRiskService extends AbstractService
{
    protected $rolfTagTable;
    protected $MonarcObjectTable;
    protected $instanceTable;
    protected $instanceRiskOpTable;
    protected $instanceRiskOpService;
    protected $measureTable;
    protected $referentialTable;
    protected $dependencies = ['measures'];
    protected $filterColumns = [
        'code', 'label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4'
    ];

    /**
     * @inheritdoc
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $tag = null, $anr = null)
    {
        $filterAnd = [];
        $filterJoin = [];

        if ($tag !== null) {
            $filterJoin[] = [
                'as' => 'g',
                'rel' => 'tags'
            ];
            $filterAnd['g.id'] = $tag;
        }

        if ($anr !== null) {
            $filterAnd['anr'] = (int)$anr;
        }

        return $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd,
            $filterJoin
        );
    }

    /**
     * @inheritdoc
     */
    public function getFilteredSpecificCount(
        $page = 1,
        $limit = 25,
        $order = null,
        $filter = null,
        $tag = null,
        $anr = null
    ) {
        $filterAnd = [];
        $filterJoin = [];

        if ($tag !== null) {
            $filterJoin[] = [
                'as' => 'g',
                'rel' => 'tags'
            ];
            $filterAnd['g.id'] = $tag;
        }

        if ($anr !== null) {
            $filterAnd['anr'] = (int)$anr;
        }

        return $this->get('table')->countFiltered(
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd,
            $filterJoin
        );
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        /** @var RolfRiskTable $rolfRiskTable */
        $rolfRiskTable = $this->get('table');
        $entityClass = $rolfRiskTable->getEntityClass();

        /** @var RolfRiskSuperClass $rolfRisk */
        $rolfRisk = new $entityClass();
        $rolfRisk->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data);

        $anr = null;
        if (!empty($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->findById((int)$data['anr']);
            $rolfRisk->setAnr($anr);
        }

        foreach ($data['measures'] as $measure) {
            /** @var MeasureTable $measureTable */
            $measureTable = $this->get('measureTable');
            if (isset($measure['uuid'], $measure['anr'], $anr)) {
                $rolfRisk->addMeasure($measureTable->findByAnrAndUuid($anr, $measure['uuid']));
            } else {
                $rolfRisk->addMeasure($measureTable->findByUuid((string)$measure));
            }
        }

        if (!empty($data['tags'])) {
            /** @var RolfTagTable $rolfTagTable */
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($data['tags'] as $rolfTagId) {
                $rolfTag = $rolfTagTable->findById($rolfTagId);
                $rolfRisk->addTag($rolfTag);
            }
        }

        // Create operation instance risks with the linked rolf tags.
        /** @var InstanceRiskOpTable $instanceRiskOpTable */
        $instanceRiskOpTable = $this->get('instanceRiskOpTable');
        /** @var InstanceRiskOpService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('MonarcObjectTable');
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        foreach ($rolfRisk->getTags() as $addedRolfTag) {
            if ($anr === null) {
                $objects = $monarcObjectTable->findByRolfTag($addedRolfTag);
            } else {
                // TODO: it's wired that we call here an empty method.
                $objects = $monarcObjectTable->findByAnrAndRolfTag($anr, $addedRolfTag);
            }
            foreach ($objects as $object) {
                foreach ($object->getInstances() as $instance) {
                    $instanceRiskOpService->createInstanceRiskOpWithScales(
                        $instance,
                        $object,
                        $rolfRisk
                    );
                }

                $instanceRiskOpTable->getDb()->flush();
            }
        }

        $rolfRisk->setUpdater($this->getConnectedUser()->getEmail());

        $rolfRiskTable->saveEntity($rolfRisk);

        return $rolfRisk->getId();
    }

    /**
     * @inheritdoc
     * set the deleted risks in specific
     */
    public function delete($id)
    {
        $instanceRiskOpTable = $this->get('instanceRiskOpTable');
        $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['rolfRisk' => $id]);
        $nbInstancesRisksOp = \count($instancesRisksOp);
        foreach ($instancesRisksOp as $i => $instanceRiskOp) {
            $instanceRiskOp->specific = 1;
            $instanceRiskOpTable->save($instanceRiskOp, ($i + 1) === $nbInstancesRisksOp);
        }

        return parent::delete($id);
    }

    /**
     * @inheritdoc
     * set the deleted risks in specific
     */
    public function deleteListFromAnr($data, $anrId = null)
    {
        foreach ($data as $ro) {
            $this->delete($ro);
        }
    }
    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $rolfTags = $data['tags'] ?? [];
        unset($data['tags']);

        /** @var RolfRiskTable $rolfRiskTable */
        $rolfRiskTable = $this->get('table');

        $rolfRisk = $rolfRiskTable->findById($id);
        //manage the measures separately because it's the slave of the relation RolfRisks<-->measures
        foreach ($data['measures'] as $measure) {
            $this->get('measureTable')->getEntity($measure)->addOpRisk($rolfRisk);
        }
        foreach ($rolfRisk->getMeasures() as $m) {
            if (!\in_array($m->getUuid(), array_column($data['measures'], 'uuid'), true)) {
                $m->deleteOpRisk($rolfRisk);
            }
        }
        unset($data['measures']);
        $rolfRisk->setDbAdapter($rolfRiskTable->getDb());
        $rolfRisk->setLanguage($this->getLanguage());
        $rolfRisk->exchangeArray($data);
        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($rolfRisk, $dependencies);

        $currentTagId = [];
        foreach ($rolfRisk->getTags() as $tag) {
            $currentTagId[] = $tag->getId();
        }

        $rolfRisk->get('tags')->initialize();

        foreach ($rolfRisk->get('tags') as $rolfTag) {
            if (\in_array($rolfTag->getId(), $rolfTags, true)) {
                unset($rolfTags[\array_search($rolfTag->getId(), $rolfTags)]);
            } else {
                $rolfRisk->get('tags')->removeElement($rolfTag);
            }
        }

        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $rolfRisk->setTag($key, $rolfTag);
                }
            }
        }

        $this->setDependencies($rolfRisk, ['anr']);

        $newTagId = [];
        foreach ($rolfRisk->getTags() as $tag) {
            $newTagId[] = $tag->getId();
        }

        $deletedTags = [];
        foreach ($currentTagId as $tagId) {
            if (!\in_array($tagId, $newTagId, true)) {
                $deletedTags[] = $tagId;
            }
        }

        $addedTags = [];
        foreach ($newTagId as $tagId) {
            if (!\in_array($tagId, $currentTagId, true)) {
                $addedTags[] = $tagId;
            }
        }

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('MonarcObjectTable');
        /** @var InstanceRiskOpTable $instanceRiskOpTable */
        $instanceRiskOpTable = $this->get('instanceRiskOpTable');
        foreach ($deletedTags as $deletedTag) {
            // TODO: this wont work...
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $deletedTag]);
            foreach ($objects as $object) {
                $instancesRisksOp = $instanceRiskOpTable->findByObjectAndRolfRisk($object, $rolfRisk);

                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $instanceRiskOp->setIsSpecific(true);
                    $instanceRiskOpTable->saveEntity($instanceRiskOp, false);
                }

                $instanceRiskOpTable->getDb()->flush();
            }
        }

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        /** @var InstanceRiskOpService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        foreach ($addedTags as $addedTag) {
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $addedTag]);
            foreach ($objects as $object) {
                try {
                    $instances = $instanceTable->getEntityByFields(['object' => $object->getUuid()]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'object' => [
                            'anr' => $data['anr'],
                            'uuid' => $object->getUuid()
                        ]
                    ]);
                }

                foreach ($instances as $instance) {
                    $instanceRiskOpService->createInstanceRiskOpWithScales(
                        $instance,
                        $object,
                        $rolfRisk
                    );
                }

                $instanceRiskOpTable->getDb()->flush();
            }
        }

        foreach ($currentTagId as $currentTag) {
            // manage the fact that label can changed for OP risk
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $currentTag]);
            foreach ($objects as $object) {
                $instancesRisksOp = $instanceRiskOpTable->findByObjectAndRolfRisk($object, $rolfRisk);

                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $instanceRiskOp->setRiskCacheCode($rolfRisk->getCode())
                        ->setRiskCacheLabels([
                            'riskCacheLabel1' => $rolfRisk->getLabel(1),
                            'riskCacheLabel2' => $rolfRisk->getLabel(2),
                            'riskCacheLabel3' => $rolfRisk->getLabel(3),
                            'riskCacheLabel4' => $rolfRisk->getLabel(4),
                        ])
                        ->setRiskCacheDescriptions([
                            'riskCacheDescription1' => $rolfRisk->getDescription(1),
                            'riskCacheDescription2' => $rolfRisk->getDescription(2),
                            'riskCacheDescription3' => $rolfRisk->getDescription(3),
                            'riskCacheDescription4' => $rolfRisk->getDescription(4),
                        ]);

                    $instanceRiskOpTable->saveEntity($instanceRiskOp, false);
                }
            }
        }

        $rolfRiskTable->saveEntity($rolfRisk->setUpdater($this->getConnectedUser()->getEmail()));

        return $rolfRisk->getId();
    }

    /*
    * The method automatically links the Amv of the destination from the source depending on the measures_measures
    */
    public function createLinkedRisks($source_uuid, $destination)
    {
        $measuresDest = $this->get('referentialTable')->getEntity($destination)->getMeasures();
        foreach ($measuresDest as $md) {
            foreach ($md->getMeasuresLinked() as $measureLink) {
                if ($measureLink->getReferential()->getUuid() === $source_uuid) {
                    foreach ($measureLink->rolfRisks as $risk) {
                        $md->addOpRisk($risk);
                    }
                    $this->get('measureTable')->save($md, false);
                }
            }
        }
        $this->get('measureTable')->getDb()->flush();
    }
}
