<?php declare(strict_types=1);

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityManagerInterface;

/** Generates the Doctrine proxy classes for the application's default entity manager. */
final class DoctrineProxyGenerator
{
    public function generate(EntityManagerInterface $entityManager): int
    {
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $entityManager->getProxyFactory()->generateProxyClasses(
            $metadata,
            $entityManager->getConfiguration()->getProxyDir()
        );

        return count($metadata);
    }
}
