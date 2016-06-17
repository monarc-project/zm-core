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
    protected $anrService;
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

        $data = [
            'label1' => 'ANR',
            'label2' => 'ANR',
            'label3' => 'ANR',
            'label4' => 'ANR',
        ];
        $this->get('anrService')->create($data);

        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        return $this->get('table')->save($entity);
    }
}