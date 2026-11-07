<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Entity\GuideItem;
use Monarc\Core\Entity\User;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\GuideService;
use Monarc\Core\Service\GuideItemService;
use Monarc\Core\Table\GuideTable;
use Monarc\Core\Table\GuideItemTable;
use Monarc\Core\Validator\InputValidator\Guide\GuideDataInputValidator;
use Monarc\Core\Validator\InputValidator\Guide\GuideItemDataInputValidator;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

class GuideTestEnvironment
{
    public EntityManager $manager;
    public GuideTable $guides;
    public GuideItemTable $items;
    public GuideService $guideService;
    public GuideItemService $itemService;
    public GuideDataInputValidator $guideValidator;
    public GuideItemDataInputValidator $itemValidator;

    public function __construct(ConnectedUserService $identity)
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [__DIR__ . '/../../src/Entity']));
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Php84GuideTestProxies');
        $config->setAutoGenerateProxyClasses(true);
        $this->manager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $metadata = array_map(
            fn (string $class) => $this->manager->getClassMetadata($class),
            [Guide::class, GuideItem::class]
        );
        // SQLite indexes have a database-wide namespace; MySQL's existing names are per table.
        foreach ((new SchemaTool($this->manager))->getCreateSchemaSql($metadata) as $sql) {
            $sql = preg_replace('/^(CREATE INDEX) (\w+) ON (\w+)/', '$1 $3_$2 ON $3', $sql);
            $this->manager->getConnection()->executeStatement($sql);
        }
        $this->guides = new GuideTable($this->manager);
        $this->items = new GuideItemTable($this->manager);
        $translator = new InputValidationTranslator($identity, ['defaultLanguageIndex' => 3]);
        $this->guideValidator = new GuideDataInputValidator([], $translator);
        $this->itemValidator = new GuideItemDataInputValidator([], $translator);
        $this->guideService = new GuideService($this->guides, $this->items, $identity);
        $this->itemService = new GuideItemService(
            $this->items,
            $this->guides,
            $this->guideService,
            $identity
        );
    }

    public static function user(): User
    {
        return new User([
            'firstname' => 'Guide', 'lastname' => 'Editor', 'email' => 'guide@example.invalid',
            'language' => 2, 'role' => [], 'creator' => 'PHP84-02',
        ]);
    }
}
