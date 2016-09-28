<?php

namespace MonarcCore\Controller;

use MonarcCore\Service\ObjectService;
use Zend\View\Model\JsonModel;

class ApiAnrLibraryController extends AbstractController
{
    protected $name = 'categories';

    protected $dependencies = ['anr', 'parent'];

    public function getList()
    {
        $anrId = $this->params()->fromRoute('anrid');

        /** @var ObjectService $service */
        $service = $this->getService();
        $objectsCategories = $service->getCategoriesLibraryByAnr($anrId);

        $this->formatDependencies($objectsCategories, $this->dependencies);

        $fields = ['id', 'label1', 'label2', 'label3', 'label4', 'position', 'objects'];
        $objectsCategories = $this->recursiveArray($objectsCategories, null, 0, $fields);

        return new JsonModel(array(
            $this->name => $objectsCategories
        ));

    }

    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Create
     *
     * @param mixed $data
     * @return JsonModel
     * @throws \Exception
     */
    public function create($data)
    {
        $anrId = $this->params()->fromRoute('anrid');

        if (!isset($data['objectId'])) {
            throw new \Exception('objectId is missing');
        }

        /** @var ObjectService $service */
        $service = $this->getService();
        $id = $service->attachObjectToAnr($data['objectId'], $anrId);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
    }

    public function update($id, $data)
    {
        return $this->methodNotAllowed();
    }

    public function patch($id, $data)
    {
        return $this->methodNotAllowed();
    }

    public function delete($id)
    {
        $anrId = $this->params()->fromRoute('anrid');

        /** @var ObjectService $service */
        $service = $this->getService();
        $service->detachObjectToAnr($id, $anrId);

        return new JsonModel(
            array(
                'status' => 'ok'
            )
        );

    }
}

