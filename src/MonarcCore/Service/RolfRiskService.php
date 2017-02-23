<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Table\InstanceRiskOpTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ObjectTable;

/**
 * Rolf Risk Service
 *
 * Class RolfRiskService
 * @package MonarcCore\Service
 */
class RolfRiskService extends AbstractService
{
    protected $rolfCategoryTable;
    protected $rolfTagTable;
    protected $objectTable;
    protected $instanceTable;
    protected $instanceRiskOpTable;
    protected $instanceRiskOpService;
    protected $filterColumns = [
        'code', 'label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4'
    ];

    /**
     * @inheritdoc
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $category = null, $tag = null, $anr = null)
    {
        $filterAnd = [];
        $filterJoin = [];

        if (!is_null($category)) {
            $filterJoin[] = [
                'as' => 'c',
                'rel' => 'categories'
            ];
            $filterAnd['c.id'] = $category;
        }

        if (!is_null($tag)) {
            $filterJoin[] = [
                'as' => 'g',
                'rel' => 'tags'
            ];
            $filterAnd['g.id'] = $tag;
        }

        if (!is_null($anr)) {
            $filterAnd['anr'] = intval($anr);
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
    public function getFilteredSpecificCount($page = 1, $limit = 25, $order = null, $filter = null, $category = null, $tag = null, $anr = null)
    {
        $filterAnd = [];
        $filterJoin = [];

        if (!is_null($category)) {
            $filterJoin[] = [
                'as' => 'c',
                'rel' => 'categories'
            ];
            $filterAnd['c.id'] = $category;
        }

        if (!is_null($tag)) {
            $filterJoin[] = [
                'as' => 'g',
                'rel' => 'tags'
            ];
            $filterAnd['g.id'] = $tag;
        }

        if (!is_null($anr)) {
            $filterAnd['anr'] = intval($anr);
        }


        return $this->get('table')->countFiltered(
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
    public function create($data, $last = true)
    {
        $entity = $this->get('entity');
        if (isset($data['anr']) && is_numeric($data['anr'])) {
            $data['anr'] = $this->get('anrTable')->getEntity($data['anr']);
        }

        $entity->exchangeArray($data);

        $rolfCategories = $entity->get('categories');
        if (!empty($rolfCategories)) {
            $rolfCategoryTable = $this->get('rolfCategoryTable');
            foreach ($rolfCategories as $key => $rolfCategoryId) {
                if (!empty($rolfCategoryId)) {
                    $rolfCategory = $rolfCategoryTable->getEntity($rolfCategoryId);
                    $entity->setCategory($key, $rolfCategory);
                }
            }
        }
        $rolfTags = $entity->get('tags');
        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setTag($key, $rolfTag);
                }
            }
        }
        return $this->get('table')->save($entity);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $rolfCategories = isset($data['categories']) ? $data['categories'] : [];
        unset($data['categories']);
        $rolfTags = isset($data['tags']) ? $data['tags'] : [];
        unset($data['tags']);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());
        $entity->exchangeArray($data);

        $currentTagId = [];
        foreach ($entity->tags as $tag) {
            $currentTagId[] = $tag->id;
        }

        $entity->get('categories')->initialize();
        $entity->get('tags')->initialize();

        foreach ($entity->get('categories') as $rolfCategory) {
            if (in_array($rolfCategory->get('id'), $rolfCategories)) {
                unset($rolfCategories[array_search($rolfCategory->get('id'), $rolfCategories)]);
            } else {
                $entity->get('categories')->removeElement($rolfCategory);
            }
        }

        foreach ($entity->get('tags') as $rolfTag) {
            if (in_array($rolfTag->get('id'), $rolfTags)) {
                unset($rolfTags[array_search($rolfTag->get('id'), $rolfTags)]);
            } else {
                $entity->get('tags')->removeElement($rolfTag);
            }
        }

        if (!empty($rolfCategories)) {
            $rolfCategoryTable = $this->get('rolfCategoryTable');
            foreach ($rolfCategories as $key => $rolfCategoryId) {
                if (!empty($rolfCategoryId)) {
                    $rolfCategory = $rolfCategoryTable->getEntity($rolfCategoryId);
                    $entity->setCategory($key, $rolfCategory);
                }
            }
        }

        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setTag($key, $rolfTag);
                }
            }
        }

        $this->setDependencies($entity, ['anr']);

        $newTagId = [];
        foreach ($entity->tags as $tag) {
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
            /** @var ObjectTable $objectTable */
            $objectTable = $this->get('objectTable');
            $objects = $objectTable->getEntityByFields(['rolfTag' => $deletedTag]);
            foreach ($objects as $object) {
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => $object->id, 'rolfRisk' => $id]);
                $i = 1;
                $nbInstancesRisksOp = count($instancesRisksOp);
                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $instanceRiskOp->specific = 1;
                    $instanceRiskOpTable->save($instanceRiskOp, ($i == $nbInstancesRisksOp));
                    $i++;
                }
            }
        }
        foreach ($addedTags as $addedTag) {
            /** @var ObjectTable $objectTable */
            $objectTable = $this->get('objectTable');
            $objects = $objectTable->getEntityByFields(['rolfTag' => $addedTag]);
            foreach ($objects as $object) {
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                $instances = $instanceTable->getEntityByFields(['object' => $object->id]);
                $i = 1;
                $nbInstances = count($instances);
                foreach ($instances as $instance) {
                    $data = [
                        'anr' => $object->anr->id,
                        'instance' => $instance->id,
                        'object' => $object->id,
                        'rolfRisk' => $entity->id,
                        'riskCacheCode' => $entity->code,
                        'riskCacheLabel1' => $entity->label1,
                        'riskCacheLabel2' => $entity->label2,
                        'riskCacheLabel3' => $entity->label3,
                        'riskCacheLabel4' => $entity->label4,
                        'riskCacheDescription1' => $entity->description1,
                        'riskCacheDescription2' => $entity->description2,
                        'riskCacheDescription3' => $entity->description3,
                        'riskCacheDescription4' => $entity->description4,
                    ];

                    /** @var InstanceRiskOpService $instanceRiskOpService */
                    $instanceRiskOpService = $this->get('instanceRiskOpService');
                    $instanceRiskOpService->create($data, ($i == $nbInstances));
                    $i++;
                }
            }
        }

        return $this->get('table')->save($entity);
    }
}