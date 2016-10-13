<?php
namespace MonarcCore\Model\Table;

class InstanceTable extends AbstractEntityTable {

    /**
     * Create instance to anr
     *
     * @param $instance
     * @param $anrId
     * @param $parentId
     * @param $position
     * @return mixed|null
     * @throws \Exception
     */
    public function createInstanceToAnr($anrId, $instance, $parentId, $position) {

        if (is_null($position)) {
            $filters =  ($parentId) ? ['anr' => $anrId, 'parent' => $parentId] : ['anr' => $anrId];
            $brothers = $this->getEntityByFields($filters);
            $position = count($brothers) + 1;
        }

        $instance->position = $position;

        $this->getDb()->beginTransaction();

        try {
            //create instance
            $id = $this->save($instance);

            $this->getDb()->commit();

            return $id;
        } catch (Exception $e) {
            $this->getDb()->rollBack();
            throw $e;
        }
    }

    /**
     * Find By Anr
     *
     * @param $anrId
     * @return array
     */
    public function findByAnr($anrId) {

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

    public function getAscendance($instance,$i){
        $root = $instance->get('root');
        $idRoot = null;
        $arbo = array();
        if(!empty($root)){
            $idRoot = $root->get('id');
        }
        if(!empty($idRoot)){
            $idAnr = $instance->get('anr')->get('id');
            $result = $this->getRepository()->createQueryBuilder('i')
                ->where('i.anr = :anrId')
                ->andWhere('i.root = :rootid')
                ->setParameter(':anrId', empty($idAnr)?null:$idAnr)
                ->setParameter(':rootid', $idRoot)
                ->getQuery()
                ->getResult();
            $family = array();
            foreach($result as $r){
                $family[$r->get('id')][$r->get('root')->get('id')] = $r->get('root')->getJsonArray();
            }
            $temp = array();
            $temp[] = $instance->getJsonArray();
            while(count($temp)){
                $cur = array_shift($temp);
                if(isset($family[$cur['id']])){
                    foreach($family[$cur['id']] as $id => $parent){
                        $arbo[$id] = $parent;
                        $temp[] = $parent;
                    }
                }
            }
        }
        $arbo[] = $instance->getJsonArray();
        return $arbo;
    }
}
