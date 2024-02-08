<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Table\InstanceTable;

trait InstancePositionDataHelperTrait
{
    /**
     * Is used to prepare the instance data to a compatible format for PositionUpdateTrait::updatePosition.
     */
    private function getPreparedPositionData(
        InstanceTable $instanceTable,
        InstanceSuperClass $instance,
        array $data
    ): array {
        $positionData = [];
        if (isset($data['position'])) {
            $positionData = [
                'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_START,
                'forcePositionUpdate' => true,
            ];
            if ((int)$data['position'] > 0) {
                $previousInstancePosition = $data['position'];
                /* If the instance is moved inside the same parent or root and its position <= then expected one,
                 * the previous element position is increased to 1. */
                if ($instanceTable->isEntityPersisted($instance)
                    && $previousInstancePosition >= $instance->getPosition()
                    && !$instance->arePropertiesStatesChanged($instance->getImplicitPositionRelationsValues())
                ) {
                    $previousInstancePosition++;
                }
                $previousInstance = $instanceTable->findOneByAnrParentAndPosition(
                    $instance->getAnr(),
                    $instance->getParent(),
                    $previousInstancePosition
                );
                if ($previousInstance !== null) {
                    $positionData = [
                        'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER,
                        'previous' => $previousInstance,
                        'forcePositionUpdate' => true,
                    ];
                }
            }
        }

        return $positionData;
    }
}
