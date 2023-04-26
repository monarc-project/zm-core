<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\ScaleImpactType;
use Monarc\Core\Model\Table\ScaleImpactTypeTable;
use Monarc\Core\Table\InstanceTable;

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
    protected $instanceService;
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
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $scales = parent::getList($page, $limit, $order, $filter, $filterAnd);

        $types = $this->getTypes();

        foreach ($scales as $key => $scale) {
            if (isset($scale['type'])) {
                if (isset($types[$scale['type']])) {
                    $scales[$key]['type'] = $types[$scale['type']];
                } else {
                    $scales[$key]['type'] = 'CUS'; // Custom user-defined column
                }
            }
        }

        return $scales;
    }

    public function create($data, $last = true)
    {
        $anrId = $data['anr'];
        $scales = parent::getList(1, 0, null, null, ['anr' => $anrId]);

        if (!isset($data['isSys'])) {
            $data['isSys'] = 0;
        }
        if (!isset($data['isHidden'])) {
            $data['isHidden'] = 0;
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

        if (!empty($data['labels'])) {
            $scaleImpactType->setLabels($data['labels']);
        }

        $scaleImpactType->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        // Create InstanceConsequence for each instance of the current anr.
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        /** @var InstanceConsequenceService $instanceConsequenceService */
        $instanceConsequenceService = $this->get('instanceConsequenceService');
        /** @var Instance[] $instances */
        $instances = $instanceTable->findByAnr($scaleImpactType->getAnr());
        foreach ($instances as $instance) {
            $instanceConsequenceService->createInstanceConsequence($instance, $scaleImpactType);
        }

        $this->get('table')->saveEntity($scaleImpactType);

        return $scaleImpactType->getId();
    }

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

        $scaleImpactType->setUpdater($this->getConnectedUser()->getEmail());

        return $this->get('table')->save($scaleImpactType);
    }

    public function delete($id)
    {
        $entity = $this->getEntity($id);

        if ($entity['isSys']) {
            throw new Exception('You are not authorized to do this action', '403');
        }

        $this->get('table')->delete($id);
    }

    /**
     * Hide / show scales' types on Edit Impacts dialog.
     */
    public function patch($id, $data)
    {
        $this->filterPatchFields($data);

        /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
        $scaleImpactTypeTable = $this->get('table');
        $scaleImpactType = $scaleImpactTypeTable->findById((int)$id);

        if (isset($data['isHidden'])) {
            /** @var InstanceConsequenceService $instanceConsequenceService */
            $instanceConsequenceService = $this->get('instanceConsequenceService');
            $instanceConsequenceService->updateConsequencesByScaleImpactType($scaleImpactType, (bool)$data['isHidden']);
            /** @var InstanceService $instanceService */
            $instanceService = $this->get('instanceService');
            $instanceService->refreshAllTheInstancesImpactAndUpdateRisks($scaleImpactType->getAnr());
        }

        parent::patch($id, $data);
    }
}
