<?php

namespace MonarcCore\Controller;

use MonarcCore\Service\ObjectCategoryService;
use MonarcCore\Service\ObjectService;
use Zend\View\Model\JsonModel;

class ApiAnrLibraryCategoryController extends AbstractController
{
    protected $name = 'categories';

    public function getList()
    {
        return $this->methodNotAllowed();
    }

    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    public function create($data)
    {
        return $this->methodNotAllowed();
    }

    public function update($id, $data)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Patch
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function patch($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrid');

        $data['anr'] = $anrId;

        /** @var ObjectCategoryService $service */
        $service = $this->getService();
        $service->patchLibraryCategory($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    public function delete($id)
    {
        return $this->methodNotAllowed();

    }
}

