<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\ScaleComment;

/**
 * Scale Comment Service
 *
 * Class ScaleCommentService
 * @package Monarc\Core\Service
 */
class ScaleCommentService extends AbstractService
{
    protected $anrTable;
    protected $scaleTable;
    protected $scaleService;
    protected $scaleImpactTypeService;
    protected $scaleImpactTypeTable;
    protected $dependencies = ['anr', 'scale', 'scaleImpactType'];
    protected $forbiddenFields = ['anr', 'scale'];

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        return $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        /** @var ScaleComment $scaleComment */
        $scaleComment = $this->get('entity');
        if (isset($data['scale'])) {
            $scale = $this->get('scaleTable')->getEntity($data['scale']);
            $scaleComment->setScale($scale);
            if ($scale->type != 1) {
                unset($data['scaleImpactType']);
            }
        }
        $scaleComment->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($scaleComment, $dependencies);

        $scaleComment->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($scaleComment);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        /** @var ScaleComment $scaleComment */
        $scaleComment = $this->get('table')->getEntity($id);
        if (isset($data['scale'])) {
            $scale = $this->get('scaleTable')->getEntity($data['scale']);
            $scaleComment->setScale($scale);
            if ($scale->type != 1) {
                unset($data['scaleImpactType']);
            }
        }
        $scaleComment->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($scaleComment, $dependencies);

        $scaleComment->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($scaleComment);
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        return parent::patch($id, $data);
    }
}
