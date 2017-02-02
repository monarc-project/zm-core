<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use MonarcCore\Service\ObjectCategoryService;
use Zend\View\Model\JsonModel;

/**
 * Api Anr Library Category Controller
 *
 * Class ApiAnrLibraryCategoryController
 * @package MonarcCore\Controller
 */
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