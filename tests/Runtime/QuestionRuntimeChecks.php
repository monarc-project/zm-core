<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Runtime;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\SchemaValidator;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Question\GetQuestionsInputFormatter;
use Monarc\Core\InputFormatter\Question\GetQuestionChoicesInputFormatter;
use Monarc\Core\Service\QuestionService;
use Monarc\Core\Service\QuestionChoiceService;
use Psr\Container\ContainerInterface;

/** Local, session-only temporary tables keep all installed question data intact. */
class QuestionRuntimeChecks
{
    public static function run(ContainerInterface $container, string $office, string &$phase): array
    {
        $managers = ['core' => $container->get('doctrine.entitymanager.orm_default')];
        if ($office === 'FrontOffice') {
            $managers['analysis'] = $container->get('doctrine.entitymanager.orm_cli');
        }
        $temporaryTables = [];
        try {
            foreach ($managers as $scope => $manager) {
                $phase = $scope . ' question metadata';
                $namespace = $scope === 'core' ? 'Monarc\\Core' : 'Monarc\\FrontOffice';
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
            $phase = 'question core CRUD';
            $service = $container->get(QuestionService::class);
            $choices = $container->get(QuestionChoiceService::class);
            $prefix = $office === 'FrontOffice' ? 'Monarc\\FrontOffice' : 'Monarc\\BackOffice';
            $controllerPrefix = $office === 'FrontOffice' ? 'ApiAnrQuestions' : 'ApiQuestions';
            $controllers = $container->get('ControllerManager');
            $controllers->get($prefix . '\\Controller\\' . $controllerPrefix . 'Controller');
            if ($office === 'BackOffice') {
                $controllers->get($prefix . '\\Controller\\' . $controllerPrefix . 'ChoicesController');
            } else {
                self::check(
                    !$controllers->has($prefix . '\\Controller\\' . $controllerPrefix . 'ChoicesController'),
                    'FrontOffice choice controller is absent'
                );
            }
            $q = $service->create(
                $questionDataInputValidator->validate(['label3' => 'First', 'type' => 2, 'multichoice' => 0])
            );
            self::check(
                (int)$managers['core']->getConnection()->fetchOne(
                    'SELECT COUNT(*) FROM questions WHERE id = ?',
                    [$q]
                ) === 1,
                'core question service writes to the common manager'
            );
            $other = $service->create($questionDataInputValidator->validate([
                'label3' => 'Second', 'type' => 1, 'multichoice' => 0, 'implicitPosition' => 1,
            ]));
            self::check($service->getEntity($q)['position'] === 2, 'insertion');
            $service->update(
                $q,
                $questionDataInputValidator->validate(['implicitPosition' => 1, 'label4' => 'Fourth'], true)
            );
            self::check($service->getEntity($other)['position'] === 2, 'movement');
            $choices->replaceList(
                $questionChoiceDataInputValidator->validateReplacement(
                    ['questionId' => $q, 'choice' => [['label3' => 'A'], ['label3' => 'B']]]
                )
            );
            self::check(
                (int)$managers['core']->getConnection()->fetchOne(
                    'SELECT COUNT(*) FROM questions_choices WHERE question_id = ?',
                    [$q]
                ) === 2,
                'core choice service writes to the common manager'
            );
            $params = (new GetQuestionChoicesInputFormatter())->format(['question' => $q]);
            $ids = array_column($choices->getList($params), 'id');
            self::reject(fn () => $choices->replaceList($questionChoiceDataInputValidator->validateReplacement([
                'questionId' => $q, 'choice' => [
                    ['label3' => 'Valid'], ['label1' => ['invalid']],
                ],
            ])));
            self::check($ids === array_column($choices->getList($params), 'id'), 'invalid replacement is atomic');
            $choices->replaceList(
                $questionChoiceDataInputValidator->validateReplacement(['questionId' => $q, 'choice' => []])
            );
            self::check($choices->getCount($params) === 0, 'empty replacement');
            $result = [
                'coreCrud' => 'passed', 'questionMappings' => 'clean', 'controllers' => 'resolved',
                'requiredQuestion' => 'metadata/schema checked; NULL inserts rejected',
            ];
            if ($office === 'FrontOffice') {
                $phase = 'analysis question CRUD';
                $manager = $managers['analysis'];
                $ids = $manager->getConnection()->fetchFirstColumn('SELECT id FROM anrs ORDER BY id LIMIT 2');
                self::check(count($ids) === 2, 'two local analyses required for scope validation');
                $table = $container->get(\Monarc\FrontOffice\Table\AnrTable::class);
                $anr = $table->findById((int)$ids[0]);
                $foreign = $table->findById((int)$ids[1]);
                $questions = $container->get(\Monarc\FrontOffice\Service\AnrQuestionService::class);
                $analysisQuestionDataInputValidator = $container->get(
                    \Monarc\FrontOffice\Validator\InputValidator\Question\QuestionDataInputValidator::class
                );
                $snapshot = $container->get(\Monarc\FrontOffice\Service\QuestionSnapshotService::class);
                $custom = $questions->create(
                    $analysisQuestionDataInputValidator->validate(['label3' => 'Custom']),
                    $anr
                );
                $questions->update(
                    $custom,
                    $analysisQuestionDataInputValidator->validate(['response' => '0'], true),
                    $anr
                );
                self::check($questions->getEntity($custom, $anr)['response'] === '0', 'zero response');
                self::reject(fn () => $questions->getEntity($custom, $foreign), EntityNotFoundException::class);
                $phase = 'analysis import and cloning';
                $snapshot->replace($anr, ['questions' => [1 => [
                    'id' => 100, 'type' => 2, 'isMultiChoice' => false, 'label' => 'Question',
                    'mode' => 0, 'response' => '200',
                    'questionChoices' => [['id' => 200, 'label' => 'Answer', 'position' => 1]],
                ]]]);
                $source = $container->get(\Monarc\FrontOffice\Table\QuestionTable::class)->findOrderedQuestions($anr);
                $copied = $snapshot->copy($source, $foreign);
                $manager->flush();
                $snapshot->completeCopy($copied);
                $copies = $questions->getList((new GetQuestionsInputFormatter())->format([]), $foreign);
                self::check(
                    count($copies) === 1 && $copies[0]['response'] === (string)$copies[0]['choices'][1]['id'],
                    'single choice remapping'
                );
                $snapshot->replace($anr, ['questions' => []]);
                self::check(
                    $questions->getCount((new GetQuestionsInputFormatter())->format([]), $anr) === 0,
                    'empty question import'
                );
                $result['analysisCrudScopeAndCopy'] = 'passed';
            }
            $phase = 'question delete ordering';
            $service->delete($q);
            self::check($service->getEntity($other)['position'] === 1, 'delete ordering');
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
