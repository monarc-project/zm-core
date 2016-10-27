<?php
namespace MonarcCore\Controller;

use MonarcCore\Service\ModelService;
use Zend\View\Model\JsonModel;

/**
 * Api Models Duplication Controller
 *
 * Class ApiModelsDupicationController
 * @package MonarcCore\Controller
 */
class ApiModelsDuplicationController extends AbstractController
{
    protected $dependencies = ['anr'];
    protected $name = 'models';

    public function getList()
    {
        return $this->methodNotAllowed();
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
        if (!isset($data['model'])) {
            throw new \Exception('Model missing', 412);
        }

        /** @var ModelService $modelService */
        $modelService = $this->getService();
        $id = $modelService->duplicate($data['model']);

        return new JsonModel([
            'status' => 'ok',
            'id' => $id,
        ]);
    }

    public function delete($id)
    {
        return $this->methodNotAllowed();
    }

    public function patch($id, $data)
    {
        return $this->methodNotAllowed();
    }


    public function update($id, $data)
    {
        return $this->methodNotAllowed();
    }
}

