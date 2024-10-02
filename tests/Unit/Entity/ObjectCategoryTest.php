<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Monarc\Core\Entity\MonarcObject;
use Monarc\Core\Entity\ObjectCategory;
use PHPUnit\Framework\TestCase;

class ObjectCategoryTest extends TestCase
{
    /**
     * @covers ObjectCategory::getObjectsRecursively
     */
    public function testFetchingObjectsRecursively(): void
    {
        /* Has 3 objects inside. */
        $rootObjectCategory = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 2 objects inside. */
        $childObjectCategory1 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 4 objects inside. */
        $childObjectCategory2 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 1 object inside. */
        $subChildObjectCategory1OfChild1 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 3 objects inside. */
        $subChildObjectCategory2OfChild2 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 2 objects inside */
        $subChildObjectCategory3OfChild2 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 2 objects inside */
        $subSubChildObjectCategoryOfSubChildObjCat1 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class))
            ->addObject($this->createMock(MonarcObject::class));
        /* Has 1 object inside. */
        $subSubChildObjectCategoryOfSubChildObjCat3 = (new ObjectCategory())
            ->addObject($this->createMock(MonarcObject::class));

        $rootObjectCategory
            ->addChild(
                $childObjectCategory1->addChild(
                    $subChildObjectCategory1OfChild1->addChild($subSubChildObjectCategoryOfSubChildObjCat1)
                )
            )
            ->addChild(
                $childObjectCategory2
                    ->addChild($subChildObjectCategory2OfChild2)
                    ->addChild(
                        $subChildObjectCategory3OfChild2->addChild($subSubChildObjectCategoryOfSubChildObjCat3)
                    )
            );

        static::assertCount(
            18,
            $rootObjectCategory->getObjectsRecursively(),
            'The number of objects linked to all the sub categories has to be equal 18.'
        );
    }

    /**
     * @covers ObjectCategory::getRecursiveChildrenIds
     */
    public function testFetchingCategoriesIdsRecursively(): void
    {
        $rootObjectCategory = static::setProtectedProperty((new ObjectCategory), 'id', 1);
        $childObjectCategory1 = static::setProtectedProperty((new ObjectCategory), 'id', 2);
        $childObjectCategory2 = static::setProtectedProperty((new ObjectCategory), 'id', 3);
        $subChildObjectCategory1OfChild1 = static::setProtectedProperty((new ObjectCategory), 'id', 4);
        $subChildObjectCategory2OfChild2 = static::setProtectedProperty((new ObjectCategory), 'id', 5);
        $subChildObjectCategory3OfChild2 = static::setProtectedProperty((new ObjectCategory), 'id', 6);
        $subSubChildObjectCategoryOfSubChildObjCat1 = static::setProtectedProperty((new ObjectCategory), 'id', 7);
        $subSubChildObjectCategoryOfSubChildObjCat3 = static::setProtectedProperty((new ObjectCategory), 'id', 8);

        $subChildObjectCategory3OfChild2 = static::setProtectedProperty(
            $subChildObjectCategory3OfChild2,
            'children',
            new ArrayCollection([$subSubChildObjectCategoryOfSubChildObjCat3])
        );
        $subChildObjectCategory1OfChild1 = static::setProtectedProperty(
            $subChildObjectCategory1OfChild1,
            'children',
            new ArrayCollection([$subSubChildObjectCategoryOfSubChildObjCat1])
        );
        $childObjectCategory1 = static::setProtectedProperty(
            $childObjectCategory1,
            'children',
            new ArrayCollection([$subChildObjectCategory1OfChild1])
        );
        $childObjectCategory2 = static::setProtectedProperty(
            $childObjectCategory2,
            'children',
            new ArrayCollection([$subChildObjectCategory2OfChild2, $subChildObjectCategory3OfChild2])
        );
        $rootObjectCategory = static::setProtectedProperty(
            $rootObjectCategory,
            'children',
            new ArrayCollection([$childObjectCategory1, $childObjectCategory2])
        );

        /** @var ObjectCategory $rootObjectCategory */
        $result = $rootObjectCategory->getRecursiveChildrenIds();
        static::assertCount(
            7,
            $result,
            'The number of objects linked to all the sub categories has to be equal 7.'
        );
        static::assertEquals([2, 4, 7, 3, 5, 6, 8], $result);
    }

    protected static function setProtectedProperty(object $object, string $property, $value): object
    {
        $reflectionObject = new \ReflectionObject($object);
        $property = $reflectionObject->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        return $object;
    }
}
