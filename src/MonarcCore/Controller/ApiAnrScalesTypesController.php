<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Controller;

use MonarcCore\Service\ScaleImpactTypeService;
use Zend\View\Model\JsonModel;

/**
 * Api Anr Scales Types Controller
 *
 * Class ApiAnrScalesTypesController
 * @package MonarcCore\Controller
 */
class ApiAnrScalesTypesController extends AbstractController
{
    protected $dependencies = [];
    protected $name = 'types';

    /**
     * @inheritdoc
     */
    public function getList()
    {
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $anrId = (int) $this->params()->fromRoute('anrId');

        /** @var ScaleImpactTypeService $service */
        $service = $this->getService();
        $types = $service->getList(0, 0, $order, $filter, ['anr' => $anrId]);

        return new JsonModel(array(
            'count' => count($types),
            $this->name => $types
        ));
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');

        $data['anr'] = $anrId;

        /** @var ScaleImpactTypeService $service */
        $service = $this->getService();
        $service->patch($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }
}