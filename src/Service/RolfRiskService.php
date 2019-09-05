<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\MonarcObjectTable;
use Monarc\FrontOffice\Model\Entity\RolfRisk;

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
    public function getFilteredSpecificCount($page = 1, $limit = 25, $order = null, $filter = null, $tag = null, $anr = null)
    {
        $filterAnd = [];
        $filterJoin = [];

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
        /** @var RolfRisk $entity */
        $addedTags = [];
        $class = $this->get('entity');
        $table = $this->get('table');
        $entity = new $class();
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($table->getDb());
        if (isset($data['anr']) && is_numeric($data['anr'])) {
            $data['anr'] = $this->get('anrTable')->getEntity($data['anr']);

        }
        //manage the measures separatly because it's the slave of the relation RolfRisks<-->measures
        foreach ($data['measures'] as $measure) {
          $measureEntity = $this->get('measureTable')->getEntity($measure);
          $measureEntity->AddOpRisk($entity);
        }
        unset($data['measures']);
        $entity->exchangeArray($data);

        $rolfTags = $entity->get('tags');
        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setTag($key, $rolfTag);
                    $addedTags[] = $rolfTagId;
                }
            }
        }
        $opId = $this->get('table')->save($entity);
        //manage the addition of tags
        foreach ($addedTags as $addedTag) {
            /** @var MonarcObjectTable $MonarcObjectTable */
            $MonarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $MonarcObjectTable->getEntityByFields(['rolfTag' => $addedTag]);
            foreach ($objects as $object) {
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                try{
                  $instances = $instanceTable->getEntityByFields(['object' => $object->uuid->toString()]);
                }catch(QueryException $e){
                  $instances = $instanceTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()]]);
                } catch (MappingException $e) {
                    $instances = $instanceTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()]]);
                }
                $i = 1;
                $nbInstances = count($instances);
                foreach ($instances as $instance) {
                    $data = [
                        'rolfRisk' => $opId,
                        'anr' => $object->anr->id,
                        'instance' => $instance->id,
                        'object' => $object->uuid->toString(),
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

        $this->get('table')->save($entity);
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
      $nbInstancesRisksOp = count($instancesRisksOp);
      $i=1;
      foreach ($instancesRisksOp as $instanceRiskOp) {
          $instanceRiskOp->specific = 1;
          $instanceRiskOpTable->save($instanceRiskOp, ($i == $nbInstancesRisksOp));
          $i++;
      }
      return parent::delete($id);
    }

    /**
     * @inheritdoc
     * set the deleted risks in specific
     */
    public function deleteListFromAnr($data,$anrId = NULL)
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

        $rolfTags = isset($data['tags']) ? $data['tags'] : [];
        unset($data['tags']);

        $entity = $this->get('table')->getEntity($id);
        //manage the measures separatly because it's the slave of the relation RolfRisks<-->measures
        foreach ($data['measures'] as $measure) {
          $measureEntity = $this->get('measureTable')->getEntity($measure);
          $measureEntity->AddOpRisk($entity);
        }
        foreach ($entity->measures as $m) {
            if(false === array_search($m->uuid->toString(), array_column($data['measures'], 'uuid'),true)){
              $m->deleteOpRisk($entity);
            }
        }
        unset($data['measures']);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());
        $entity->exchangeArray($data);
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $currentTagId = [];
        foreach ($entity->tags as $tag) {
            $currentTagId[] = $tag->id;
        }

        $entity->get('tags')->initialize();

        foreach ($entity->get('tags') as $rolfTag) {
            if (in_array($rolfTag->get('id'), $rolfTags)) {
                unset($rolfTags[array_search($rolfTag->get('id'), $rolfTags)]);
            } else {
                $entity->get('tags')->removeElement($rolfTag);
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
            /** @var MonarcObjectTable $MonarcObjectTable */
            $MonarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $MonarcObjectTable->getEntityByFields(['rolfTag' => $deletedTag]);
            foreach ($objects as $object) {
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                try{
                  $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => $object->uuid->toString(), 'rolfRisk' => $id]);
                }catch(QueryException $e){
                  $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()], 'rolfRisk' => $id]);
                } catch (MappingException $e) {
                    $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()], 'rolfRisk' => $id]);
                }
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
            /** @var MonarcObjectTable $MonarcObjectTable */
            $MonarcObjectTable = $this->get('MonarcObjectTable');
            $objects = $MonarcObjectTable->getEntityByFields(['rolfTag' => $addedTag]);
            foreach ($objects as $object) {
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                try{
                  $instances = $instanceTable->getEntityByFields(['object' => $object->uuid->toString()]);
                }catch(QueryException $e){
                  $instances = $instanceTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()]]);
                } catch (MappingException $e) {
                    $instances = $instanceTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()]]);
                }
                $i = 1;
                $nbInstances = count($instances);
                foreach ($instances as $instance) {
                    $data = [
                        'anr' => $object->anr->id,
                        'instance' => $instance->id,
                        'object' => $object->uuid->toString(),
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

        foreach ($currentTagId as $currentTag) {
          // manage the fact that label can changed for OP risk
          $MonarcObjectTable = $this->get('MonarcObjectTable');
          $objects = $MonarcObjectTable->getEntityByFields(['rolfTag' => $currentTag]);
          foreach ($objects as $object) {
            $instanceRiskOpTable = $this->get('instanceRiskOpTable');
            try{
              $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => $object->uuid->toString(), 'rolfRisk' => $id] );
            }catch(QueryException $e){
              $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()], 'rolfRisk' => $id]);
            } catch (MappingException $e) {
                $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['object' => ['anr' => $data['anr'],'uuid' => $object->uuid->toString()], 'rolfRisk' => $id]);
            }

            $nbInstances = count($instancesRisksOp);
            //update label
            foreach ($instancesRisksOp as $instance) {
                $data = [
                    'anr' => $object->anr->id,
                    'object' => $object->uuid->toString(),
                    'rolfRisk' => $entity->id,
                    'riskCacheLabel1' => $entity->label1,
                    'riskCacheLabel2' => $entity->label2,
                    'riskCacheLabel3' => $entity->label3,
                    'riskCacheLabel4' => $entity->label4,
                    'riskCacheDescription1' => $entity->description1,
                    'riskCacheDescription2' => $entity->description2,
                    'riskCacheDescription3' => $entity->description3,
                    'riskCacheDescription4' => $entity->description4,
                ];
                $instanceRiskOpService = $this->get('instanceRiskOpService');
                $instanceRiskOpService->update($instance->id, $data); // on update l'instance

              }
            }
          }
        return $this->get('table')->save($entity);
    }

    /*
    * Function to link automatically the amv of the destination from the source depending of the measures_measures
    */
      public function createLinkedRisks($source_uuid, $destination)
      {
        $measures_dest = $this->get('referentialTable')->getEntity($destination)->getMeasures();
        foreach ($measures_dest as $md) {
          foreach ($md->getMeasuresLinked() as $measureLink) {
              if($measureLink->getReferential()->getuuid()->toString()==$source_uuid){
                foreach ($measureLink->rolfRisks as $risk) {
                  $md->AddOpRisk($risk);
                }
                $this->get('measureTable')->save($md,false);
              }
          }
        }
        $this->get('measureTable')->getDb()->flush();
      }
}