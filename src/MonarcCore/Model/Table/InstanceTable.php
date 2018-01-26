<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Table;

/**
 * Class InstanceTable
 * @package MonarcCore\Model\Table
 */
class InstanceTable extends AbstractEntityTable
{
    /**
     * Find By Anr
     *
     * @param $anrId
     * @return array
     */
    public function findByAnr($anrId)
    {
        return $this->getRepository()->createQueryBuilder('i')
            ->select(array(
                'i.id', 'i.level', 'IDENTITY(i.parent) as parentId',
                'i.c', 'i.i', 'i.d', 'i.ch', 'i.ih', 'i.dh',
                'i.name1', 'i.name2', 'i.name3', 'i.name4',
                'i.label1', 'i.label2', 'i.label3', 'i.label4'
            ))
            ->where('i.anr = :anrId')
            ->setParameter(':anrId', $anrId)
            ->orderBy('i.parent', 'ASC')
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get Ascendance
     *
     * @param $instance
     * @return array
     */
    public function getAscendance($instance)
    {
        $root = $instance->get('root');
        $idRoot = null;
        $arbo[] = $instance->getJsonArray();
        if (!empty($root)) {
            $idRoot = $root->get('id');
        }
        if (!empty($idRoot)) {
            $idAnr = $instance->get('anr')->get('id');
            $result = $this->getRepository()->createQueryBuilder('i')
                ->where('i.anr = :anrId')
                ->andWhere('i.root = :rootid')
                ->setParameter(':anrId', empty($idAnr) ? null : $idAnr)
                ->setParameter(':rootid', $idRoot)
                ->getQuery()
                ->getResult();
            $family = array();
            foreach ($result as $r) {
                $p = $r->get('parent');
                if (!empty($p)) {//Cyril - Fix Trello [#9KkvKj5M] - not sure it's enough, what if we want the anr itself (null)
                    $family[$r->get('id')][$r->get('parent')->get('id')] = $r->get('parent')->getJsonArray();
                }
            }
            $temp = array();
            $temp[] = $instance->getJsonArray();
            while (count($temp)) {
                $cur = array_shift($temp);
                if (isset($family[$cur['id']])) {
                    foreach ($family[$cur['id']] as $id => $parent) {
                        $arbo[$id] = $parent;
                        $temp[] = $parent;
                    }
                }
            }
        }
        return $arbo;
    }

    /**
     * Build Where For Position Create
     *
     * @param $params
     * @param $queryBuilder
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param string $newOrOld
     * @return mixed
     */
    protected function buildWhereForPositionCreate($params, $queryBuilder, \MonarcCore\Model\Entity\AbstractEntity $entity, $newOrOld = 'new')
    {
        $queryBuilder = parent::buildWhereForPositionCreate($params, $queryBuilder, $entity, $newOrOld);
        $anr = $entity->get('anr');
        if ($anr) {
            $queryBuilder = $queryBuilder->andWhere('t.anr = :anr')
                ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
        } else {
            $queryBuilder = $queryBuilder->andWhere('t.anr IS NULL');
        }
        return $queryBuilder;
    }

    /**
     * Manage Delete Position
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param array $params
     */
    protected function manageDeletePosition(\MonarcCore\Model\Entity\AbstractEntity $entity, $params = array())
    {
        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position - 1');
        $hasWhere = false;
        if (!empty($params['field'])) {
            $hasWhere = true;
            if (is_null($entity->get($params['field']))) {
                $return = $return->where('t.' . $params['field'] . ' IS NULL');
            } else {
                $return = $return->where('t.' . $params['field'] . ' = :' . $params['field'])
                    ->setParameter(':' . $params['field'], $entity->get($params['field']));
            }
        }

        $anr = $entity->get('anr');
        if ($anr) {
            $return = $return->andWhere('t.anr = :anr')
                ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
        } else {
            $return = $return->andWhere('t.anr IS NULL');
        }

        if ($hasWhere) {
            $return = $return->andWhere('t.position >= :pos');
        } else {
            $return = $return->where('t.position >= :pos');
        }
        $return = $return->setParameter(':pos', $entity->get('position'));
        $return->getQuery()->getResult();
    }

    /**
     * Count Position Max
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param array $params
     * @return mixed
     */
    protected function countPositionMax(\MonarcCore\Model\Entity\AbstractEntity $entity, $params = array())
    {
        $return = $this->getRepository()->createQueryBuilder('t')
            ->select('COUNT(t.id)');
        if (!empty($params['field'])) {
            if (isset($params['newField'][$params['field']])) {
                if (is_null($params['newField'][$params['field']])) {
                    $return = $return->where('t.' . $params['field'] . ' IS NULL');
                } else {
                    $return = $return->where('t.' . $params['field'] . ' = :' . $params['field'])
                        ->setParameter(':' . $params['field'], $params['newField'][$params['field']]);
                }
            }
        }
        $anr = $entity->get('anr');
        if ($anr) {
            $return = $return->andWhere('t.anr = :anr')
                ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
        } else {
            $return = $return->andWhere('t.anr IS NULL');
        }

        $id = $entity->get('id');
        return $return->getQuery()->getSingleScalarResult() + ($id ? 0 : 1);
    }
}