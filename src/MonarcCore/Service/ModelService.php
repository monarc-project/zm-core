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
        $entity->setLanguage($this->getLanguage());

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

        // If we reached here, our object is ready to be saved.
        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data){
        if (array_key_exists('isRegulator', $data) && array_key_exists('isGeneric', $data) &&
            $data['isRegulator'] && $data['isGeneric']) {
            throw new \Exception("A regulator model may not be generic", 412);
        }

        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        parent::update($id, $data);
    }

    protected function resetCurrentDefault() {
        $this->get('table')->resetCurrentDefault();
    }
}