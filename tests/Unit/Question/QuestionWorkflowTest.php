<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Question;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\SchemaValidator;
use Monarc\Core\Entity\AbstractEntity;
use Monarc\Core\Entity\Question;
use Monarc\Core\Entity\QuestionChoice;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Question\GetQuestionsInputFormatter;
use Monarc\Core\InputFormatter\Question\GetQuestionChoicesInputFormatter;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Tests\Fixture\GuideTestEnvironment;
use Monarc\Core\Tests\Fixture\QuestionTestEnvironment;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class QuestionWorkflowTest extends TestCase
{
    private QuestionTestEnvironment $env;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fixture/QuestionTestEnvironment.php';
        require_once __DIR__ . '/../../Fixture/GuideTestEnvironment.php';
        $identity = $this->createMock(ConnectedUserService::class);
        $identity->method('getConnectedUser')->willReturn(GuideTestEnvironment::user());
        $this->env = new QuestionTestEnvironment($identity);
    }

    protected function tearDown(): void
    {
        $this->env->manager->close();
    }

    public function testModernMappingsAndNullableAuditValues(): void
    {
        foreach ([Question::class, QuestionChoice::class] as $class) {
            self::assertFalse(is_subclass_of($class, AbstractEntity::class));
            self::assertFalse(method_exists($class, 'exchangeArray'));
            self::assertSame([], (new SchemaValidator($this->env->manager))
                ->validateClass($this->env->manager->getClassMetadata($class)));
            $entity = new $class();
            self::assertNull($entity->getCreatedAt());
            self::assertSame('', $entity->getCreator());
            self::assertSame('', $entity->getUpdater());
        }
    }

    public function testChoiceRequiresItsQuestionInMappingAndPersistence(): void
    {
        $question = $this->question('Required parent');
        $choice = $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $question, 'label1' => 'Answer'])
        );
        self::assertSame('guide@example.invalid', $this->env->service->getQuestionData($question)['creator']);
        self::assertSame('guide@example.invalid', $this->env->choiceService->getQuestionChoiceData($choice)['creator']);
        $metadata = $this->env->manager->getClassMetadata(QuestionChoice::class);
        self::assertFalse($metadata->associationMappings['question']['joinColumns'][0]['nullable']);
        self::assertSame($question, $this->env->choices->findById($choice)->getQuestion()->getId());
        $this->env->manager->clear();
        self::assertSame($question, $this->env->choices->findById($choice)->getQuestion()->getId());
        $this->expectException(\Doctrine\DBAL\Exception\NotNullConstraintViolationException::class);
        $this->env->manager->getConnection()->insert('questions_choices', ['question_id' => null, 'position' => 1]);
    }

    public function testQuestionInsertionMovementDeletionAndPartialEdits(): void
    {
        $a = $this->question('A');
        $b = $this->question('B', ['implicitPosition' => 1]);
        $c = $this->question('C', ['implicitPosition' => 3, 'previous' => $b]);
        self::assertSame(['B', 'C', 'A'], $this->labels());
        $this->env->service->update(
            $a,
            $this->env->validator->validate(['implicitPosition' => 1, 'label4' => 'Other'], true)
        );
        self::assertSame(['A', 'B', 'C'], $this->labels());
        self::assertSame('Other', $this->env->service->getQuestionData($a)['label4']);
        self::assertSame('guide@example.invalid', $this->env->service->getQuestionData($a)['updater']);
        $this->env->service->update(
            $b,
            $this->env->validator->validate(['position' => 70000, 'multichoice' => false], true)
        );
        self::assertSame(['A', 'C', 'B'], $this->labels());
        $this->reject(
            fn () => $this->env->service->update(
                $a,
                $this->env->validator->validate(['implicitPosition' => 3, 'previous' => $a], true)
            )
        );
        $this->env->service->delete($c);
        self::assertSame([1, 2], array_column($this->rows(), 'position'));
        $this->env->service->update(
            $a,
            $this->env->validator->validate(['label1' => null, 'label2' => '', 'multichoice' => 0], true)
        );
        self::assertSame('A', $this->env->service->getQuestionData($a)['label1']);
        self::assertSame('', $this->env->service->getQuestionData($a)['label2']);
        $this->env->manager->clear();
        self::assertSame('A', $this->env->service->getQuestionData($a)['label1']);
    }

    public function testChoiceReplacementValidatesEveryRowBeforeRemovalAndEmptyReplacementFlushes(): void
    {
        $q = $this->question('Choices');
        $other = $this->question('Other');
        $this->env->choiceService->replaceList($this->env->choiceValidator->validateReplacement([
            'questionId' => $q, 'choice' => [
                ['label1' => 'One', 'position' => 88], ['label3' => 'Two'],
            ],
        ]));
        self::assertSame([1, 2], array_column($this->choices($q), 'position'));
        $ids = array_column($this->choices($q), 'id');
        $this->reject(fn () => $this->env->choiceService->replaceList(
            $this->env->choiceValidator->validateReplacement(['questionId' => $q, 'choice' => [
                ['label1' => 'Valid'], ['label2' => ['invalid']],
            ]])
        ));
        self::assertSame($ids, array_column($this->choices($q), 'id'));
        $this->env->choiceService->replaceList($this->env->choiceValidator->validateReplacement(['questionId' => $q,
            'choice' => [['label1' => 'Content only', 'question' => $other]]]));
        self::assertSame('Content only', $this->choices($q)[0]['label1']);
        self::assertSame([], $this->choices($other));
        $this->env->choiceService->replaceList(
            $this->env->choiceValidator->validateReplacement(['questionId' => $q, 'choice' => []])
        );
        $this->env->manager->clear();
        self::assertSame([], $this->choices($q));
    }

    /** @dataProvider invalidReplacements */
    public function testMalformedReplacementsNeverScheduleDeletion($choices): void
    {
        $q = $this->question('Kept');
        $c = $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $q, 'label1' => 'Kept'])
        );
        $this->reject(
            fn () => $this->env->choiceService->replaceList(
                $this->env->choiceValidator->validateReplacement(['questionId' => $q, 'choice' => $choices])
            )
        );
        // Flush afterwards to catch invalid requests that left scheduled ORM removals behind.
        $this->env->choices->flush();
        self::assertSame([$c], array_column($this->choices($q), 'id'));
    }

    public static function invalidReplacements(): array
    {
        return [[null], [7], [['named' => ['label1' => 'Invalid']]], [[false]],
            [[['label3' => false]]]];
    }

    public function testChoiceCrudAndMovingBetweenQuestionsKeepsCollectionsAndOrdering(): void
    {
        $q = $this->question('Choices');
        $other = $this->question('Other');
        $a = $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $q, 'label1' => 'A'])
        );
        $b = $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $q, 'label1' => 'B', 'position' => 1])
        );
        self::assertSame(['B', 'A'], array_column($this->choices($q), 'label1'));
        $this->env->choiceService->update($b, $this->env->choiceValidator->validate(['position' => 70000], true));
        self::assertSame(['A', 'B'], array_column($this->choices($q), 'label1'));
        $this->env->choiceService->update($b, $this->env->choiceValidator->validate(['question' => $other], true));
        self::assertSame([1], array_column($this->choices($q), 'position'));
        self::assertSame($other, $this->env->choices->findById($b)->getQuestion()->getId());
        self::assertInstanceOf(\stdClass::class, $this->env->choiceService->getQuestionChoiceData($b)['question']);
        $this->env->manager->clear();
        self::assertCount(1, $this->env->questions->findById($other)->getQuestionChoices());
        $this->env->service->delete($other);
        self::assertSame(1, $this->env->choices->countAll());
        $this->env->choiceService->delete($a);
        self::assertSame(0, $this->env->choices->countAll());
    }

    public function testInvalidCreatesDeletesAndFullUpdatesAreRejectedBeforeMutation(): void
    {
        $this->reject(
            fn () => $this->env->service->create($this->env->validator->validate(['type' => 7, 'multichoice' => 0]))
        );
        self::assertSame(0, $this->env->questions->countAll());
        $q = $this->question('Kept');
        $this->reject(
            fn () => $this->env->service->deleteList($this->env->validator->validateIds([$q, 999])),
            EntityNotFoundException::class
        );
        $this->reject(
            fn () => $this->env->service->update(
                $q,
                $this->env->validator->validate(['label1' => 'Invalid PUT'], false)
            )
        );
        $this->reject(
            fn () => $this->env->choiceService->create($this->env->choiceValidator->validate(['question' => 999])),
            EntityNotFoundException::class
        );
        self::assertSame('Kept', $this->env->service->getQuestionData($q)['label1']);
        self::assertSame(0, $this->env->choices->countAll());
    }

    public function testListAndCountShareSearchFiltersAndPayloadFields(): void
    {
        $q = $this->question('Search', ['multichoice' => 1]);
        $this->question('Else');
        $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $q, 'label4' => 'Fourth'])
        );
        $params = (new GetQuestionsInputFormatter())->format(['filter' => 'Search', 'type' => 2, 'limit' => 1]);
        self::assertSame(1, $this->env->service->getCount($params));
        $row = $this->env->service->getList($params)[0];
        self::assertSame($q, $row['id']);
        self::assertSame(1, $row['multichoice']);
        self::assertSame('Fourth', $row['choices'][0]['label4']);
        self::assertArrayHasKey('questionChoices', $row);
        self::assertArrayHasKey('createdAt', $row);
        self::assertArrayNotHasKey('anr', $row);
    }

    /** @dataProvider invalidData */
    public function testInvalidScalarSemanticsAreRejected(array $data): void
    {
        $this->reject(
            fn () => $this->env->service->create(
                $this->env->validator->validate(array_merge(['type' => 1, 'multichoice' => 0], $data))
            )
        );
    }

    public static function invalidData(): array
    {
        return [[['type' => false]], [['type' => null]], [['multichoice' => 2]], [['position' => -1]],
            [['label1' => false]], [['label2' => str_repeat('x', 256)]], [['response' => []]]];
    }

    public function testGlobalDefinitionsIgnoreAnalysisFieldsFromThePayload(): void
    {
        $id = $this->question('Global', ['anr' => 22]);
        self::assertSame('Global', $this->env->service->getQuestionData($id)['label1']);
        self::assertArrayNotHasKey('anr', $this->env->service->getQuestionData($id));
    }

    /** @dataProvider invalidQueries */
    public function testQueryFieldsAreConstrained(array $query): void
    {
        $this->reject(fn () => (new GetQuestionsInputFormatter())->format($query));
    }

    public static function invalidQueries(): array
    {
        return [[['order' => 'position;DROP TABLE questions']], [['order' => ['id']]], [['filter' => []]],
            [['limit' => -1]], [['type' => 4]], [['multichoice' => 5]]];
    }

    private function question(string $label, array $data = []): int
    {
        return $this->env->service->create(
            $this->env->validator->validate(array_merge(['label1' => $label, 'type' => 2, 'multichoice' => 0], $data))
        );
    }

    private function rows(): array
    {
        return $this->env->service->getList((new GetQuestionsInputFormatter())->format([]));
    }

    private function labels(): array
    {
        return array_column($this->rows(), 'label1');
    }

    private function choices(int $question): array
    {
        return $this->env->choiceService->getList((new GetQuestionChoicesInputFormatter())->format([
            'question' => $question,
        ]));
    }

    private function reject(callable $action, string $exceptionClass = Exception::class): void
    {
        try {
            $action();
            self::fail('Expected validation failure');
        } catch (\Exception $error) {
            self::assertInstanceOf($exceptionClass, $error);
            if ($error instanceof Exception) {
                self::assertSame(412, $error->getCode());
            }
        }
    }
}
