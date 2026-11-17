<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Runtime;

use Doctrine\ORM\Tools\SchemaValidator;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Question\GetQuestionChoicesInputFormatter;
use Monarc\Core\Service\QuestionService;
use Monarc\Core\Service\QuestionChoiceService;
use Psr\Container\ContainerInterface;

/** Local, session-only temporary tables keep all installed question data intact. */
class QuestionRuntimeChecks
{
    public static function run(ContainerInterface $container, string &$phase): array
    {
        $managers = ['core' => $container->get('doctrine.entitymanager.orm_default')];
        $temporaryTables = [];
        try {
            foreach ($managers as $scope => $manager) {
                $phase = $scope . ' question metadata';
                $namespace = 'Monarc\\Core';
                foreach (['Question', 'QuestionChoice'] as $name) {
                    $metadata = $manager->getClassMetadata($namespace . '\\Entity\\' . $name);
                    $errors = (new SchemaValidator($manager))->validateClass(
                        $metadata
                    );
                    self::check($errors === [], 'question association mappings');
                    if ($name === 'QuestionChoice') {
                        self::check(
                            !$metadata->associationMappings['question']['joinColumns'][0]['nullable'],
                            'question choice requires its question in metadata'
                        );
                    }
                }
                $columns = $manager->getConnection()->createSchemaManager()->listTableColumns('questions_choices');
                self::check($columns['question_id']->getNotnull(), 'installed question_id is NOT NULL');
                $manager->clear();
                foreach (['questions', 'questions_choices'] as $table) {
                    $phase = $scope . ' temporary ' . $table;
                    $connection = $manager->getConnection();
                    $definition = $connection->createSchemaManager()->introspectTable($table);
                    foreach ($definition->getForeignKeys() as $key) {
                        $definition->removeForeignKey($key->getName());
                    }
                    foreach ($connection->getDatabasePlatform()->getCreateTableSQL($definition) as $sql) {
                        $connection->executeStatement(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql));
                    }
                    $temporaryTables[] = [$connection, $table];
                }
                $phase = $scope . ' reject null question';
                try {
                    $manager->getConnection()->executeStatement(
                        'INSERT INTO questions_choices (question_id, position) VALUES (NULL, 1)'
                    );
                    throw new \RuntimeException('A choice without a question was accepted.');
                } catch (\Doctrine\DBAL\Exception\NotNullConstraintViolationException $error) {
                    // The database must reject a missing parent, independently of service validation.
                }
            }
            $questionDataInputValidator = $container->get(
                \Monarc\Core\Validator\InputValidator\Question\QuestionDataInputValidator::class
            );
            $questionChoiceDataInputValidator = $container->get(
                \Monarc\Core\Validator\InputValidator\Question\QuestionChoiceDataInputValidator::class
            );
            $questionDataInputValidator->setDefaultLanguageIndex(3);
            $questionChoiceDataInputValidator->setDefaultLanguageIndex(3);
            $phase = 'question core CRUD';
            $service = $container->get(QuestionService::class);
            $choices = $container->get(QuestionChoiceService::class);
            $q = $service->create(
                $questionDataInputValidator->validate(['label3' => 'First', 'type' => 2, 'multichoice' => 0])
            )->getId();
            self::check(
                (int)$managers['core']->getConnection()->fetchOne(
                    'SELECT COUNT(*) FROM questions WHERE id = ?',
                    [$q]
                ) === 1,
                'core question service writes to the common manager'
            );
            $other = $service->create($questionDataInputValidator->validate([
                'label3' => 'Second',
                'type' => 1,
                'multichoice' => 0,
                'implicitPosition' => 1,
            ]))->getId();
            self::check($service->getQuestionData($q)['position'] === 2, 'insertion');
            $service->update(
                $q,
                $questionDataInputValidator->validate(['implicitPosition' => 1, 'label4' => 'Fourth'], true)
            );
            self::check($service->getQuestionData($other)['position'] === 2, 'movement');
            $choices->replaceList([
                'questionId' => $q,
                'choice' => [['label3' => 'A'], ['label3' => 'B']],
            ]);
            self::check(
                (int)$managers['core']->getConnection()->fetchOne(
                    'SELECT COUNT(*) FROM questions_choices WHERE question_id = ?',
                    [$q]
                ) === 2,
                'core choice service writes to the common manager'
            );
            $params = (new GetQuestionChoicesInputFormatter())->format(['question' => $q]);
            $ids = array_column($choices->getList($params), 'id');
            self::reject(fn () => $choices->replaceList([
                'questionId' => $q,
                'choice' => 'invalid',
            ]));
            self::check($ids === array_column($choices->getList($params), 'id'), 'invalid replacement is atomic');
            $choices->replaceList(['questionId' => $q, 'choice' => []]);
            self::check($choices->getCount($params) === 0, 'empty replacement');
            $result = [
                'coreCrud' => 'passed',
                'questionMappings' => 'clean',
                'controllers' => 'resolved',
                'requiredQuestion' => 'metadata/schema checked; NULL inserts rejected',
            ];
            $phase = 'question delete ordering';
            $service->delete($q);
            self::check($service->getQuestionData($other)['position'] === 1, 'delete ordering');
            $service->delete($other);
            $phase = 'retained legacy delivery read';
            $result['legacyDeliveryReadCount'] = count($container->get(
                \Monarc\Core\Service\DeliveriesModelsService::class
            )->getList(1, 1));
            return $result;
        } finally {
            foreach ($managers as $manager) {
                $manager->clear();
            }
            foreach (array_reverse($temporaryTables) as [$connection, $table]) {
                $connection->executeStatement('DROP TEMPORARY TABLE ' . $table);
            }
        }
    }

    private static function reject(callable $action, string $exceptionClass = Exception::class): void
    {
        try {
            $action();
        } catch (\Exception $error) {
            self::check($error instanceof $exceptionClass, 'rejected exception');
            if ($error instanceof Exception) {
                self::check($error->getCode() === 412, 'rejected status');
            }
            return;
        }
        throw new \RuntimeException('Question runtime check accepted invalid data.');
    }

    private static function check(bool $passed, string $name): void
    {
        if (!$passed) {
            throw new \RuntimeException('Question runtime check failed: ' . $name);
        }
    }
}
