<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Scales Comments Controller
 *
 * Class ApiAnrScalesCommentsController
 * @package MonarcCore\Controller
 */
class ApiAnrScalesCommentsController extends AbstractController
{
    protected $dependencies = ['scale', 'scaleImpactType'];
    protected $name = 'comments';

    /**
     * @inheritdoc
     */
    public function getList()
    {
        $page = $this->params()->fromQuery('page');
        $limit = $this->params()->fromQuery('limit');
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $anrId = (int) $this->params()->fromRoute('anrId');
        $scale = (int) $this->params()->fromRoute('scaleId');

        $comments = $this->getService()->getList($page, $limit, $order, $filter, ['anr' => $anrId, 'scale' => $scale]);
        foreach($comments as $key => $type){
            $this->formatDependencies($comments[$key], $this->dependencies);
        }

        return new JsonModel(array(
            'count' => count($comments),
            'anr' => $anrId,
            'scale' => $scale,
            $this->name => $comments
        ));
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
        $anrId = (int) $this->params()->fromRoute('anrId');
        $scaleId = (int) $this->params()->fromRoute('scaleId');

        $data['anr'] = $anrId;
        $data['scale'] = $scaleId;

        $id = $this->getService()->create($data);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');
        $scaleId = (int) $this->params()->fromRoute('scaleId');

        $data['anr'] = $anrId;
        $data['scale'] = $scaleId;

        $id = $this->getService()->update($id,$data);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
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
    public function delete($id)
    {
        return $this->methodNotAllowed();
    }
}