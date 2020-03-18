<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\RolfRisk;
use Monarc\Core\Model\Entity\RolfRiskSuperClass;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\MonarcObjectTable;
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
    public function getFilteredSpecificCount($page = 1, $limit = 25, $order = null, $filter = null, $tag = null, $anr = null)
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
        $addedTags = [];
        /** @var RolfRiskSuperClass $rolfRisk */
        $rolfRisk = $this->get('entity');

        if (isset($data['anr']) && is_numeric($data['anr'])) {
            $data['anr'] = $this->get('anrTable')->getEntity($data['anr']);

        }
        //manage the measures separatly because it's the slave of the relation RolfRisks<-->measures
        foreach ($data['measures'] as $measure) {
            $this->get('measureTable')->getEntity($measure)->addOpRisk($rolfRisk);
        }
        unset($data['measures']);
        $rolfRisk->exchangeArray($data);

        $rolfTags = $rolfRisk->get('tags');
        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $rolfRisk->setTag($key, $rolfTag);
                    $addedTags[] = $rolfTagId;
                }
            }
        }

        $rolfRisk->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $opId = $this->get('table')->save($rolfRisk);

        $data = [
            'rolfRisk' => $opId,
            'riskCacheCode' => $rolfRisk->code,
            'riskCacheLabel1' => $rolfRisk->label1,
            'riskCacheLabel2' => $rolfRisk->label2,
            'riskCacheLabel3' => $rolfRisk->label3,
            'riskCacheLabel4' => $rolfRisk->label4,
            'riskCacheDescription1' => $rolfRisk->description1,
            'riskCacheDescription2' => $rolfRisk->description2,
            'riskCacheDescription3' => $rolfRisk->description3,
            'riskCacheDescription4' => $rolfRisk->description4,
        ];
        //manage the addition of tags
        foreach ($addedTags as $addedTag) {
            /** @var MonarcObjectTable $monarcObjectTable */
            $monarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $addedTag]);
            foreach ($objects as $object) {
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                try {
                    $instances = $instanceTable->getEntityByFields(['object' => (string)$object->uuid]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'object' => [
                            'anr' => $data['anr'],
                            'uuid' => (string)$object->uuid
                        ]
                    ]);
                }

                $nbInstances = \count($instances);

                $data['object'] = (string)$object->uuid;
                $data['anr'] = $object->anr->id;

                foreach ($instances as $i => $instance) {
                    $data['instance'] = $instance->id;

                    /** @var InstanceRiskOpService $instanceRiskOpService */
                    $instanceRiskOpService = $this->get('instanceRiskOpService');
                    $instanceRiskOpService->create($data, ($i + 1) === $nbInstances);
                }
            }
        }

        return $opId;
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

        /** @var RolfRisk $rolfRisk */
        $rolfRisk = $this->get('table')->getEntity($id);
        //manage the measures separatly because it's the slave of the relation RolfRisks<-->measures
        foreach ($data['measures'] as $measure) {
            $this->get('measureTable')->getEntity($measure)->addOpRisk($rolfRisk);
        }
        foreach ($rolfRisk->getMeasures() as $m) {
            if (!\in_array($m->uuid->toString(), array_column($data['measures'], 'uuid'), true)) {
                $m->deleteOpRisk($rolfRisk);
            }
        }
        unset($data['measures']);
        $rolfRisk->setDbAdapter($this->get('table')->getDb());
        $rolfRisk->setLanguage($this->getLanguage());
        $rolfRisk->exchangeArray($data);
        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($rolfRisk, $dependencies);

        $currentTagId = [];
        foreach ($rolfRisk->tags as $tag) {
            $currentTagId[] = $tag->id;
        }

        $rolfRisk->get('tags')->initialize();

        foreach ($rolfRisk->get('tags') as $rolfTag) {
            if (in_array($rolfTag->get('id'), $rolfTags)) {
                unset($rolfTags[array_search($rolfTag->get('id'), $rolfTags)]);
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
        foreach ($rolfRisk->tags as $tag) {
            $newTagId[] = $tag->id;
        }

        $deletedTags = [];
        foreach ($currentTagId as $tagId) {
            if (!in_array($tagId, $newTagId)) {
                $deletedTags[] = $tagId;
            }
        }

        $addedTags = [];
        foreach ($newTagId as $tagId) {
            if (!in_array($tagId, $currentTagId)) {
                $addedTags[] = $tagId;
            }
        }

        foreach ($deletedTags as $deletedTag) {
            /** @var MonarcObjectTable $monarcObjectTable */
            $monarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $deletedTag]);
            foreach ($objects as $object) {
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                try {
                    $instancesRisksOp = $instanceRiskOpTable->getEntityByFields([
                        'object' => (string)$object->uuid,
                        'rolfRisk' => $id,
                    ]);
                } catch (QueryException | MappingException $e) {
                    $instancesRisksOp = $instanceRiskOpTable->getEntityByFields([
                        'object' => [
                            'anr' => $data['anr'],
                            'uuid' => (string)$object->uuid
                        ],
                        'rolfRisk' => $id
                    ]);
                }

                $i = 1;
                $nbInstancesRisksOp = \count($instancesRisksOp);
                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $instanceRiskOp->specific = 1;
                    $instanceRiskOpTable->save($instanceRiskOp, $i == $nbInstancesRisksOp);
                    $i++;
                }
            }
        }

        $data = [
            'anr' => $data['anr'],
            'rolfRisk' => $rolfRisk->id,
            'riskCacheCode' => $rolfRisk->code,
            'riskCacheLabel1' => $rolfRisk->label1,
            'riskCacheLabel2' => $rolfRisk->label2,
            'riskCacheLabel3' => $rolfRisk->label3,
            'riskCacheLabel4' => $rolfRisk->label4,
            'riskCacheDescription1' => $rolfRisk->description1,
            'riskCacheDescription2' => $rolfRisk->description2,
            'riskCacheDescription3' => $rolfRisk->description3,
            'riskCacheDescription4' => $rolfRisk->description4,
        ];

        foreach ($addedTags as $addedTag) {
            /** @var MonarcObjectTable $MonarcObjectTable */
            $monarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $addedTag]);
            foreach ($objects as $object) {
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                try {
                    $instances = $instanceTable->getEntityByFields(['object' => (string)$object->uuid]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'object' => [
                            'anr' => $data['anr'],
                            'uuid' => $object->uuid->toString()
                        ]
                    ]);
                }

                $i = 1;
                $nbInstances = \count($instances);

                $data['object'] = $object->uuid->toString();

                foreach ($instances as $instance) {
                    $data['instance'] = $instance->id;

                    /** @var InstanceRiskOpService $instanceRiskOpService */
                    $instanceRiskOpService = $this->get('instanceRiskOpService');
                    $instanceRiskOpService->create($data, $i === $nbInstances);
                    $i++;
                }
            }
        }

        foreach ($currentTagId as $currentTag) {
            // manage the fact that label can changed for OP risk
            $monarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $monarcObjectTable->getEntityByFields(['rolfTag' => $currentTag]);
            foreach ($objects as $object) {
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                try {
                    $instancesRisksOp = $instanceRiskOpTable->getEntityByFields([
                        'object' => $object->uuid->toString(),
                        'rolfRisk' => $id,
                    ]);
                } catch (QueryException | MappingException $e) {
                    $instancesRisksOp = $instanceRiskOpTable->getEntityByFields([
                        'object' => [
                            'anr' => $data['anr'],
                            'uuid' => $object->uuid->toString(),
                        ],
                        'rolfRisk' => $id,
                    ]);
                }

                $data['object'] = $object->uuid->toString();

                //update label
                foreach ($instancesRisksOp as $instance) {
                    $instanceRiskOpService = $this->get('instanceRiskOpService');
                    $instanceRiskOpService->update($instance->id, $data); // on update l'instance
                }
            }
        }

        $rolfRisk->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($rolfRisk);
    }

    /*
    * The method automatically links the Amv of the destination from the source depending on the measures_measures
    */
    public function createLinkedRisks($source_uuid, $destination)
    {
        $measuresDest = $this->get('referentialTable')->getEntity($destination)->getMeasures();
        foreach ($measuresDest as $md) {
            foreach ($md->getMeasuresLinked() as $measureLink) {
                if ((string)$measureLink->getReferential()->getUuid() === (string)$source_uuid) {
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
