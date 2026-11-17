<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use InvalidArgumentException;
use Monarc\Core\Entity\ScenarioTemplatePackageLink;
use PHPUnit\Framework\TestCase;

/** @covers \Monarc\Core\Entity\ScenarioTemplatePackageLink */
final class ScenarioTemplatePackageLinkTest extends TestCase
{
    public function testDoctrineMapsTheSourceTypeAsANonNullableStringLimitedToThirtyCharacters(): void
    {
        $config = Setup::createAnnotationMetadataConfiguration(
            [dirname(__DIR__, 3) . '/src/Entity'], true, null, null, false
        );
        $em = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $metadata = $em->getClassMetadata(ScenarioTemplatePackageLink::class);
        self::assertSame('scenario_catalog_template_package_links', $metadata->getTableName());
        $mapping = $metadata->getFieldMapping('sourceType');
        self::assertSame('source_type', $mapping['columnName']);
        self::assertSame('string', $mapping['type']);
        self::assertSame(30, $mapping['length']);
        self::assertFalse($mapping['nullable']);
    }

    public function testSourceTypesAreValidatedAgainstTheEntityConstants(): void
    {
        $link = new ScenarioTemplatePackageLink();
        foreach (ScenarioTemplatePackageLink::SOURCE_TYPES as $type) {
            self::assertSame($type, $link->setSourceType($type)->getSourceType());
        }
        self::assertContains(
            ScenarioTemplatePackageLink::SOURCE_TYPE_REFERENTIAL,
            ScenarioTemplatePackageLink::AUTHORABLE_SOURCE_TYPES
        );
        self::assertNotContains(
            ScenarioTemplatePackageLink::SOURCE_TYPE_CONTROL,
            ScenarioTemplatePackageLink::AUTHORABLE_SOURCE_TYPES
        );
    }

    public function testRemovedRecommendationTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ScenarioTemplatePackageLink())->setSourceType('recommendation');
    }

}
