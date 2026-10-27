<?php declare(strict_types=1);

namespace Unit\Service;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Proxy\ProxyFactory;
use Monarc\Core\Service\DoctrineProxyGenerator;
use PHPUnit\Framework\TestCase;

final class DoctrineProxyGeneratorTest extends TestCase
{
    /**
     * @covers DoctrineProxyGenerator::generate
     */
    public function testGeneratesEveryMappedEntityIntoTheConfiguredProxyDirectory(): void
    {
        $metadata = [
            $this->createMock(ClassMetadata::class),
            $this->createMock(ClassMetadata::class),
        ];
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects(self::once())->method('getAllMetadata')->willReturn($metadata);

        $proxyFactory = $this->createMock(ProxyFactory::class);
        $proxyFactory->expects(self::once())
            ->method('generateProxyClasses')
            ->with($metadata, '/var/cache/monarc/doctrine-proxies');

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects(self::once())
            ->method('getProxyDir')
            ->willReturn('/var/cache/monarc/doctrine-proxies');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('getMetadataFactory')->willReturn($metadataFactory);
        $entityManager->expects(self::once())->method('getProxyFactory')->willReturn($proxyFactory);
        $entityManager->expects(self::once())->method('getConfiguration')->willReturn($configuration);

        self::assertSame(2, (new DoctrineProxyGenerator())->generate($entityManager));
    }
}
