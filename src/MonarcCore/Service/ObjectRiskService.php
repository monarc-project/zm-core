<?php
namespace MonarcCore\Service;

/**
 * Object Risk Service
 *
 * Class ObjectRiskService
 * @package MonarcCore\Service
 */
class ObjectRiskService extends AbstractService
{
    protected $objectTable;
    protected $amvTable;
    protected $assetTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    protected $dependencies = ['object', 'amv', 'asset', 'threat', 'vulnerability'];

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id,$data){
        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }
        $entity->exchangeArray($data);

        return $this->get('table')->save($entity);
    }

}