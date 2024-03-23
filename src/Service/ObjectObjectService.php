<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Entity\Anr;
use Monarc\Core\Entity\Asset;
use Monarc\Core\Entity\MonarcObject;
use Monarc\Core\Entity\ObjectCategory;
use Monarc\Core\Entity\ObjectObject;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table\InstanceTable;
use Monarc\Core\Table\MonarcObjectTable;
use Monarc\Core\Table\ObjectCategoryTable;
use Monarc\Core\Table\ObjectObjectTable;

class ObjectObjectService
{
    use PositionUpdateTrait;

    public const MOVE_COMPOSITION_POSITION_UP = 'up';
    public const MOVE_COMPOSITION_POSITION_DOWN = 'down';

    private UserSuperClass $connectedUser;

    public function __construct(
        private ObjectObjectTable $objectObjectTable,
        private MonarcObjectTable $monarcObjectTable,
        private InstanceTable $instanceTable,
        private ObjectCategoryTable $objectCategoryTable,
        private InstanceService $instanceService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function create(array $data): ObjectObject
    {
        if ($data['parent'] === $data['child']) {
            throw new Exception('It\'s not allowed to compose the same child object as parent.', 412);
        }

        /** @var MonarcObject $parentObject */
        $parentObject = $this->monarcObjectTable->findById($data['parent']);
        /** @var MonarcObject $childObject */
        $childObject = $this->monarcObjectTable->findById($data['child']);
        if ($parentObject->hasChild($childObject)) {
            throw new Exception('The object is already presented in the composition.', 412);
        }

        if ($parentObject->isModeGeneric() && $childObject->isModeSpecific()) {
            throw new Exception('It\'s not allowed to add a specific object to a generic parent', 412);
        }

        /* Validate if one of the parents is the current child or its children. */
        $this->validateIfObjectOrItsChildrenLinkedToOneOfParents($childObject, $parentObject);

        // Ensure that we're not trying to add a specific item if the father is generic.
        /** @var Asset $asset */
        $asset = $parentObject->getAsset();
        foreach ($asset->getModels() as $model) {
            $model->validateObjectAcceptance($childObject);
        }

        /* Ensure the child object and all its children are linked to the same anrs as parent linked, link if not. */
        $this->validateAndLinkAllChildrenToAnrs($parentObject->getAnrs(), $childObject);

        /** @var ObjectObject $objectObject */
        $objectObject = (new ObjectObject())
            ->setParent($parentObject)
            ->setChild($childObject)
            ->setCreator($this->connectedUser->getEmail());

        $this->updatePositions($objectObject, $this->objectObjectTable, $data);

        $this->objectObjectTable->save($objectObject);

        /* Create instances of child object if necessary. */
        if ($parentObject->hasInstances()) {
            $this->createInstances($parentObject, $childObject, $data);
        }

        return $objectObject;
    }

    public function shiftPositionInComposition(int $id, array $data): void
    {
        /** @var ObjectObject $objectObject */
        $objectObject = $this->objectObjectTable->findById($id);

        /* Validate if the position is within the bounds of shift. */
        if (($data['move'] === static::MOVE_COMPOSITION_POSITION_UP && $objectObject->getPosition() <= 1)
            || (
                $data['move'] === static::MOVE_COMPOSITION_POSITION_DOWN
                && $objectObject->getPosition() >= $this->objectObjectTable->findMaxPosition(
                    $objectObject->getImplicitPositionRelationsValues()
                )
            )
        ) {
            return;
        }

        $positionToBeSet = $data['move'] === static::MOVE_COMPOSITION_POSITION_UP
            ? $objectObject->getPosition() - 1
            : $objectObject->getPosition() + 1;
        /** @var MonarcObject $parentObject */
        $parentObject = $objectObject->getParent();
        $previousObjectCompositionLink = $this->objectObjectTable
            ->findByParentObjectAndPosition($parentObject, $positionToBeSet);
        /* Some positions are not aligned in the DB, that's why we may have empty result. */
        if ($previousObjectCompositionLink !== null) {
            $this->objectObjectTable->save(
                $previousObjectCompositionLink->setPosition($objectObject->getPosition())->setUpdater(
                    $this->connectedUser->getEmail()
                ),
                false
            );
        }
        $this->objectObjectTable->save(
            $objectObject->setPosition($positionToBeSet)->setUpdater($this->connectedUser->getEmail())
        );
    }

    public function delete(int $id): void
    {
        /** @var ObjectObject $objectObject */
        $objectObject = $this->objectObjectTable->findById($id);

        /* Unlink the related instances of the compositions. */
        foreach ($objectObject->getChild()->getInstances() as $childObjectInstance) {
            foreach ($objectObject->getParent()->getInstances() as $parentObjectInstance) {
                if ($childObjectInstance->hasParent()
                    && $childObjectInstance->getParent()->getId() === $parentObjectInstance->getId()
                ) {
                    $childObjectInstance->setParent(null);
                    $childObjectInstance->setRoot(null);
                    $this->instanceTable->remove($childObjectInstance, false);
                }
            }
        }

        /* Shift positions to fill in the gap of the object being removed. */
        $this->shiftPositionsForRemovingEntity($objectObject, $this->objectObjectTable);

        $this->objectObjectTable->remove($objectObject);
    }

    private function validateIfObjectOrItsChildrenLinkedToOneOfParents(
        MonarcObject $childObject,
        MonarcObject $parentObject
    ): void {
        if ($parentObject->isObjectOneOfParents($childObject)) {
            throw new Exception('It\'s not allowed to make a composition with circular dependency.', 412);
        }

        foreach ($childObject->getChildren() as $childOfChildObject) {
            $this->validateIfObjectOrItsChildrenLinkedToOneOfParents($childOfChildObject, $parentObject);
        }
    }

    /**
     * New instance is created when the composition parent object is presented in the analysis.
     */
    private function createInstances(MonarcObject $parentObject, MonarcObject $childObject, array $data): void
    {
        $previousObjectCompositionLink = null;
        if ($data['implicitPosition'] === PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER) {
            /** @var ObjectObject $previousObjectCompositionLink */
            $previousObjectCompositionLink = $this->objectObjectTable->findById($data['previous']);
        }
        foreach ($parentObject->getInstances() as $parentObjectInstance) {
            $instanceData = [
                'object' => $childObject,
                'parent' => $parentObjectInstance,
                'implicitPosition' => $data['implicitPosition'],
            ];
            if ($previousObjectCompositionLink !== null) {
                foreach ($previousObjectCompositionLink->getChild()->getInstances() as $previousObjectInstance) {
                    if ($previousObjectInstance->hasParent()
                        && $previousObjectInstance->getParent()->getId() === $parentObjectInstance->getId()
                    ) {
                        $instanceData['previous'] = $previousObjectInstance->getId();
                    }
                }
            }

            /** @var Anr $anr */
            $anr = $parentObjectInstance->getAnr();
            $this->instanceService->instantiateObjectToAnr($anr, $instanceData);
        }
    }

    private function validateAndLinkAllChildrenToAnrs(iterable $anrs, MonarcObject $object): void
    {
        foreach ($anrs as $anr) {
            if (!$object->hasAnrLink($anr)) {
                $object->addAnr($anr);
                $this->monarcObjectTable->save($object, false);
            }
            /* Link the object's root category if not linked. */
            if ($object->hasCategory()) {
                /** @var ObjectCategory $rootCategory */
                $rootCategory = $object->getCategory()->getRootCategory();
                if (!$rootCategory->hasAnrLink($anr)) {
                    $rootCategory->addAnrLink($anr);
                    $this->objectCategoryTable->save($rootCategory, false);
                }
            }
        }
        foreach ($object->getChildren() as $childObject) {
            $this->validateAndLinkAllChildrenToAnrs($anrs, $childObject);
        }
    }
}
