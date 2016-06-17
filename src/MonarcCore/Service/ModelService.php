<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Entity\Model;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService
{
    protected $dependencies = ['anr'];
    protected $anrService;
    protected $anrTable;
    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');

        //anr
        $dataAnr = [
            'label1' => 'ANR',
            'label2' => 'ANR',
            'label3' => 'ANR',
            'label4' => 'ANR',
        ];
        $anrId = $this->get('anrService')->create($dataAnr);

        $data['anr'] = $anrId;

        //model
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }
}