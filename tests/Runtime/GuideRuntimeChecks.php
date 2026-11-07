<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Runtime;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Guide\GetGuidesInputFormatter;
use Monarc\Core\InputFormatter\Guide\GetGuideItemsInputFormatter;
use Monarc\Core\Service\GuideService;
use Monarc\Core\Service\GuideItemService;
use Monarc\Core\Service\QuestionService;
use Psr\Container\ContainerInterface;

/** Scoped local checks: session-only temporary tables protect all installed guide data. */
class GuideRuntimeChecks
{
    public static function run(ContainerInterface $container, string $office, string &$phase): array
    {
        $manager = $container->get('doctrine.entitymanager.orm_default');
        $connection = $manager->getConnection();
        foreach (['guides', 'guides_items'] as $name) {
            $phase = 'migrated schema ' . $name;
            $columns = $connection->createSchemaManager()->listTableColumns($name);
            self::assert(!isset($columns['anr_id']), $name . ' still contains anr_id');
            if ($name === 'guides_items') {
                self::assert($columns['guide_id']->getNotnull(), 'installed guide_id is NOT NULL');
            }
        }
        $metadata = $manager->getClassMetadata(\Monarc\Core\Entity\GuideItem::class);
        self::assert(!$metadata->associationMappings['guide']['joinColumns'][0]['nullable'], 'required guide mapping');
        $phase = 'office delivery model mapping';
        $templates = $container->get('doctrine.entitymanager.orm_cli');
        $templateClass = \Monarc\Core\Entity\DeliveriesModels::class;
        $templateService = $container->get(\Monarc\Core\Service\DeliveriesModelsService::class);
        self::assert($container->get($templateClass) instanceof $templateClass, 'registered delivery model entity');
        self::assert($templateService->get('entity') instanceof $templateClass, 'delivery service entity');
        self::assert($templateService->get('table')->getEntityClass() === $templateClass, 'delivery table entity');
        $templateMetadata = $templates->getClassMetadata($templateClass);
        $templateColumns = $templates->getConnection()->createSchemaManager()->listTableColumns('deliveries_models');
        self::assert(!$templateMetadata->hasAssociation('anr'), 'delivery templates have no analysis association');
        self::assert(!isset($templateColumns['anr_id']), 'delivery templates have no analysis column');
        if ($office === 'FrontOffice') {
            $deliveryMetadata = $templates->getClassMetadata(\Monarc\FrontOffice\Entity\Delivery::class);
            $deliveryColumns = $templates->getConnection()->createSchemaManager()->listTableColumns('deliveries');
            self::assert($deliveryMetadata->hasAssociation('anr'), 'generated delivery analysis mapping');
            self::assert(isset($deliveryColumns['anr_id']), 'generated deliveries retain their analysis column');
        }
        $phase = 'legacy delivery model read';
        $templateData = $templateService->getList(1, 1);
        $prefix = $office === 'FrontOffice' ? 'Monarc\\FrontOffice' : 'Monarc\\BackOffice';
        $guides = $container->get(GuideService::class);
        $items = $container->get(GuideItemService::class);
        $guideController = $container->get('ControllerManager')->get($prefix . '\\Controller\\ApiGuidesController');
        $itemController = $container->get('ControllerManager')->get($prefix . '\\Controller\\ApiGuidesItemsController');
        if ($office === 'BackOffice') {
            $container->get('ControllerManager')->get($prefix . '\\Controller\\ApiGuidesTypesController');
        }
        // Check the next migrated domain alongside the retained legacy delivery path.
        $phase = 'modern question read';
        $params = (new \Monarc\Core\InputFormatter\Question\GetQuestionsInputFormatter())->format(['limit' => 1]);
        $questionsRead = $container->get(QuestionService::class)->getList($params);
        $manager->clear();
        $temporaryTables = [];
        try {
            foreach (['guides', 'guides_items'] as $table) {
                $phase = 'temporary fixture ' . $table;
                $definition = $connection->createSchemaManager()->introspectTable($table);
                // Temporary InnoDB tables cannot carry foreign keys. Services still validate the references.
                foreach ($definition->getForeignKeys() as $foreignKey) {
                    $definition->removeForeignKey($foreignKey->getName());
                }
                $statements = $connection->getDatabasePlatform()->getCreateTableSQL($definition);
                foreach ($statements as $sql) {
                    $connection->executeStatement(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql));
                }
                $temporaryTables[] = $table;
            }
            $phase = 'reject null guide';
            try {
                $connection->executeStatement("INSERT INTO guides_items
                    (guide_id, position, description1, description2, description3, description4)
                    VALUES (NULL, 1, '', '', '', '')");
                throw new \RuntimeException('A guide item without a guide was accepted.');
            } catch (\Doctrine\DBAL\Exception\NotNullConstraintViolationException $error) {
                // The schema requires a guide independently of service validation.
            }
            if ($office === 'FrontOffice') {
                $phase = 'read-only FrontOffice guides';
                $guide = $guides->create(['type' => 1, 'isWithItems' => 1, 'description3' => 'Runtime guide']);
                $item = $items->create(['guide' => $guide, 'description3' => 'Runtime item']);
                self::assert(
                    self::decode($guideController->get($guide))['description3'] === 'Runtime guide',
                    'FrontOffice guide read'
                );
                self::assert(
                    self::decode($itemController->get($item))['guide']['id'] === $guide,
                    'FrontOffice item read'
                );
                foreach ([$guideController, $itemController] as $controller) {
                    foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
                        $request = (new \Laminas\Diactoros\ServerRequest([], [], '/api/guides', $method))
                            ->withAttribute(\Laminas\Router\RouteMatch::class, new \Laminas\Router\RouteMatch([]));
                        self::assert(
                            $controller->handle($request)->getStatusCode() === 405,
                            'FrontOffice guide writes are unsupported'
                        );
                    }
                }
                self::assert($guides->getEntity($guide)['description3'] === 'Runtime guide', 'guide unchanged');
                self::assert($items->getEntity($item)['description3'] === 'Runtime item', 'item unchanged');
            } else {
                $phase = 'guide create';
                $created = self::decode($guideController->create([
                    'type' => 1, 'isWithItems' => 1, 'description3' => 'PHP84-02 runtime',
                ]));
                $guide = $created['id'];
                $other = $guides->create(['type' => 2, 'isWithItems' => 0]);
                self::assert($guides->getEntity($guide)['type'] === 'Risk analysis context', 'guide label');
                try {
                    $guides->create(['type' => 1, 'isWithItems' => 1]);
                    throw new \RuntimeException('Duplicate category was accepted.');
                } catch (Exception $error) {
                    self::assert($error->getCode() === 412, 'duplicate category status');
                }
                $phase = 'item create';
                $first = self::decode($itemController->create(['guide' => $guide, 'description1' => 'First']))['id'];
                $second = $items->create(['guide' => $guide, 'description1' => 'Second', 'implicitPosition' => 1]);
                $foreign = $items->create(['guide' => $other, 'description1' => 'Other']);
                try {
                    $itemController->patch($first, ['implicitPosition' => 3, 'previous' => $foreign]);
                    throw new \RuntimeException('Foreign previous item was accepted.');
                } catch (Exception $error) {
                    self::assert($error->getCode() === 412, 'scoped item status');
                }
                $phase = 'item movement';
                $itemController->patch($first, ['implicitPosition' => 1, 'description4' => 'Updated']);
                self::assert($items->getEntity($first)['position'] === 1, 'item movement');
                self::assert($items->getEntity($second)['position'] === 2, 'sibling ordering');
                self::assert($items->getEntity($first)['description1'] === 'First', 'partial edit');
                $params = (new GetGuideItemsInputFormatter())->format(['guide' => $guide, 'filter' => 'First']);
                self::assert($items->getCount($params) === 1 && count($items->getList($params)) === 1, 'scoped count');
                $guideController->update($guide, ['type' => 1, 'isWithItems' => 0, 'description3' => 'Edited']);
                self::assert($guides->getEntity($guide)['description3'] === 'Edited', 'guide update');
                $itemController->delete($first);
                self::assert($items->getEntity($second)['position'] === 1, 'delete ordering');
                $phase = 'guide deletion';
                $guideController->deleteList([$guide, $other]);
                self::assert($guides->getCount((new GetGuidesInputFormatter())->format([])) === 0, 'guide deletion');
                self::assert($items->getCount((new GetGuideItemsInputFormatter())->format([])) === 0, 'child deletion');
            }
        } finally {
            $manager->clear();
            foreach (array_reverse($temporaryTables) as $table) {
                $connection->executeStatement('DROP TEMPORARY TABLE ' . $table);
            }
        }

        return [
            'guideCrud' => $office === 'FrontOffice' ? 'read-only' : 'passed', 'guideAnalysisColumns' => 'absent',
            'requiredGuide' => 'metadata/schema checked; NULL inserts rejected',
            'deliveryModelAnalysisColumn' => 'absent',
            'deliveryModelEntity' => $templateClass,
            'generatedDeliveryAnalysisColumn' => $office === 'FrontOffice' ? 'retained' : 'not applicable',
            'legacyDeliveryModelReadCount' => count($templateData),
            'questionReadCount' => count($questionsRead),
        ];
    }

    private static function decode(object $response): array
    {
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private static function assert(bool $condition, string $check): void
    {
        if (!$condition) {
            throw new \RuntimeException('Guide runtime check failed: ' . $check);
        }
    }
}
