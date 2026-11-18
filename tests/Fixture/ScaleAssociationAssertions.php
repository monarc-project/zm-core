<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Assert;

/** The consumer supplies its concrete entity classes; all writes use isolated SQLite. */
final class ScaleAssociationAssertions
{
    public static function checkScalePersistenceAndCopy(
        EntityManagerInterface $manager,
        string $anrClass,
        string $scaleClass,
        string $scaleCommentClass
    ): void {
        $metadata = array_map($manager->getClassMetadata(...), [$scaleClass, $scaleCommentClass]);
        foreach ((new SchemaTool($manager))->getCreateSchemaSql($metadata) as $sql) {
            $sql = preg_replace('/^(CREATE INDEX) (\w+) ON (\w+)/', '$1 $3_$2 ON $3', $sql);
            $manager->getConnection()->executeStatement($sql);
        }
        // Only the referenced analysis identity is needed; there is no analysis fixture write.
        $anr = new $anrClass();
        (new \ReflectionProperty($anr, 'id'))->setValue($anr, 1);
        $manager->getUnitOfWork()->registerManaged($anr, ['id' => 1], []);
        $scale = new $scaleClass($anr, ['type' => $scaleClass::TYPE_IMPACT, 'min' => 0, 'max' => 5]);
        $comment = (new $scaleCommentClass())->setAnr($anr)->setScale($scale)
            ->setScaleIndex(0)->setScaleValue(0)->setComments(['comment1' => 'Source']);
        Assert::assertTrue($scale->getScaleComments()->contains($comment));
        $manager->persist($scale);
        $manager->persist($comment);
        $manager->flush();
        $scaleId = $scale->getId();
        $manager->clear();
        $manager->getUnitOfWork()->registerManaged($anr, ['id' => 1], []);
        $reloaded = $manager->find($scaleClass, $scaleId);
        Assert::assertCount(1, $reloaded->getScaleComments());
        $reloadedComment = $reloaded->getScaleComments()->first();
        Assert::assertSame($reloaded, $reloadedComment->getScale());
        Assert::assertSame('Source', $reloadedComment->getComment(1));

        $copy = $scaleClass::constructFromObject($reloaded);
        $copyComment = $scaleCommentClass::constructFromObject($reloadedComment)->setAnr($anr)->setScale($copy);
        $manager->persist($copy);
        $manager->persist($copyComment);
        $manager->flush();
        Assert::assertNotSame($scaleId, $copy->getId());
        Assert::assertNotSame($reloadedComment->getId(), $copyComment->getId());
        Assert::assertSame($copy, $copyComment->getScale());
        Assert::assertCount(1, $reloaded->getScaleComments());
        Assert::assertCount(1, $copy->getScaleComments());
        $copyId = $copy->getId();
        $manager->clear();
        $manager->getUnitOfWork()->registerManaged($anr, ['id' => 1], []);
        Assert::assertSame('Source', $manager->find($scaleClass, $copyId)->getScaleComments()->first()->getComment(1));
    }
}
