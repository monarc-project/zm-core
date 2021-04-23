<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\ScaleImpactType;
use Monarc\Core\Model\Table\InstanceTable;

/**
 * Scale Type Service
 *
 * Class ScaleImpactTypeService
 * @package Monarc\Core\Service
 */
class ScaleImpactTypeService extends AbstractService
{
    protected $anrTable;
    protected $scaleTable;
    protected $instanceTable;
    protected $instanceConsequenceService;
    protected $dependencies = ['anr', 'scale'];
    protected $forbiddenFields = ['scale'];
    protected $types = [
        1 => 'C',
        2 => 'I',
        3 => 'D',
        4 => 'R',
        5 => 'O',
        6 => 'L',
        7 => 'F',
        8 => 'P',
    ];

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {

        $scales = parent::getList($page, $limit, $order, $filter, $filterAnd);

        $types = $this->getTypes();

        foreach ($scales as $key => $scale) {
            if (isset($scale['type'])) {
                if (isset($types[$scale['type']])) {
                    $scales[$key]['type'] = $types[$scale['type']];
                } else{
                    $scales[$key]['type'] = 'CUS'; // Custom user-defined column
                }
            }
        }

        return $scales;
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $anrId = $data['anr'];
        $scales = parent::getList(1,0, null, null, ['anr' => $anrId]);

        if (!isset($data['isSys'])) {
            $data['isSys'] = 0;
        }
        if (!isset($data['isHidden'])) {
            $data['isSys'] = 0;
        }
        if (!isset($data['type'])) {
            $data['type'] = count($scales) + 1;
        }

        $class = $this->get('entity');
        /** @var ScaleImpactType $scaleImpactType */
        $scaleImpactType = new $class();
        $scaleImpactType->setDbAdapter($this->get('table')->getDb());

        $scaleImpactType->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($scaleImpactType, $dependencies);

        $scaleImpactType->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $id = $this->get('table')->save($scaleImpactType);

        //retrieve all instances for current anr
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instances = $instanceTable->getEntityByFields(['anr' => $anrId]);
        $i = 1;
        $nbInstances = count($instances);
        foreach ($instances as $instance) {
            //create instances consequences
            $dataConsequences = [
                'anr' => $anrId,
                'instance' => $instance->id,
                'object' => $instance->getObject()->getUuid(),
                'scaleImpactType' => $id,
            ];
            /** @var InstanceConsequenceService $instanceConsequenceService */
            $instanceConsequenceService = $this->get('instanceConsequenceService');
            $instanceConsequenceService->create($dataConsequences, ($i == $nbInstances));
            $i++;
        }

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $data['isSys'] = 0;
        $data['type'] = 9;

        /** @var ScaleImpactType $scaleImpactType */
        $scaleImpactType = $this->get('table')->getEntity($id);
        $scaleImpactType->setDbAdapter($this->get('table')->getDb());
        //security
        $this->filterPostFields($data, $scaleImpactType);

        $scaleImpactType->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($scaleImpactType, $dependencies);

        $scaleImpactType->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($scaleImpactType);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $entity = $this->getEntity($id);

        if ($entity['isSys']) {
            throw new \Monarc\Core\Exception\Exception('You are not authorized to do this action', '403');
        }

        $this->get('table')->delete($id);
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        if (isset($data['isHidden'])) {
            $instancesConsequencesData = [
                'c' => -1,
                'i' => -1,
                'd' => -1,
                'anr' => $data['anr'],
                'isHidden' => $data['isHidden'],
            ];

            /** @var InstanceConsequenceService $instanceConsequenceService */
            $instanceConsequenceService = $this->get('instanceConsequenceService');
            $instanceConsequenceService->patchByScaleImpactType($id, $instancesConsequencesData);
        }

        parent::patch($id, $data);
    }
}
