<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

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

    /**
     * @inheritdoc
     */
    public function getList()
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function create($data)
    {
        if (!isset($data['model'])) {
            throw new \MonarcCore\Exception\Exception('Model missing', 412);
        }

        /** @var ModelService $modelService */
        $modelService = $this->getService();
        $id = $modelService->duplicate($data['model']);

        return new JsonModel([
            'status' => 'ok',
            'id' => $id,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        return $this->methodNotAllowed();
    }
}