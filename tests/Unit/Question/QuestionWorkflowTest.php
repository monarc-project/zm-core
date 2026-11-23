<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Question;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\SchemaValidator;
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

    public function testCreateReturnsThePersistedQuestionAndChoice(): void
    {
        $question = $this->env->service->create(['label1' => 'Created question', 'type' => 2, 'multichoice' => 0]);
        self::assertSame($question, $this->env->questions->findById($question->getId()));
        self::assertSame('Created question', $question->getLabels()['label1']);

        $questionChoice = $this->env->choiceService->create([
            'question' => $question->getId(),
            'label1' => 'Created choice',
        ]);
        self::assertSame($questionChoice, $this->env->choices->findById($questionChoice->getId()));
        self::assertSame($question, $questionChoice->getQuestion());
        self::assertSame('Created choice', $questionChoice->getLabels()['label1']);
    }

    public function testModernMappingsAndNullableAuditValues(): void
    {
        foreach ([Question::class, QuestionChoice::class] as $class) {
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
        )->getId();
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
            $this->env->validator->validate(['label2' => '', 'multichoice' => 0], true)
        );
        self::assertSame('A', $this->env->service->getQuestionData($a)['label1']);
        self::assertSame('', $this->env->service->getQuestionData($a)['label2']);
        $this->env->manager->clear();
        self::assertSame('A', $this->env->service->getQuestionData($a)['label1']);
    }

    public function testChoiceReplacementUsesPayloadOrderAndEmptyReplacementFlushes(): void
    {
        $q = $this->question('Choices');
        $other = $this->question('Other');
        $this->env->choiceService->replaceList([
            'questionId' => $q,
            'choice' => [
                'first' => ['label1' => 'One', 'position' => 88],
                'second' => ['label1' => 'Two', 'label3' => 'Translation'],
            ],
        ]);
        self::assertSame([1, 2], array_column($this->choices($q), 'position'));
        $ids = array_column($this->choices($q), 'id');
        $this->reject(fn () => $this->env->choiceService->replaceList([
            'questionId' => $q,
            'choice' => 'invalid',
        ]));
        self::assertSame($ids, array_column($this->choices($q), 'id'));
        $this->env->choiceService->replaceList([
            'questionId' => $q,
            'choice' => [['label1' => 'Content only', 'question' => $other]]
        ]);
        self::assertSame('Content only', $this->choices($q)[0]['label1']);
        self::assertSame([], $this->choices($other));
        $this->env->choiceService->replaceList(
            ['questionId' => $q, 'choice' => []]
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
        )->getId();
        $this->reject(
            fn () => $this->env->choiceService->replaceList(
                ['questionId' => $q, 'choice' => $choices]
            )
        );
        // Flush afterwards to catch invalid requests that left scheduled ORM removals behind.
        $this->env->choices->flush();
        self::assertSame([$c], array_column($this->choices($q), 'id'));
    }

    public static function invalidReplacements(): array
    {
        return [[null], [7], [false], ['invalid']];
    }

    public function testChoiceArrayIsRequiredOnlyForChoiceQuestionType(): void
    {
        $textQuestion = $this->question('Text', ['type' => Question::TYPE_SINGLE]);
        $this->env->choiceService->replaceList(['questionId' => $textQuestion]);
        $this->env->choiceService->replaceList(['questionId' => $textQuestion, 'choice' => 'unused']);
        self::assertSame([], $this->choices($textQuestion));

        $choiceQuestion = $this->question('Options', ['type' => Question::TYPE_CHOICE, 'multichoice' => 0]);
        $this->reject(fn () => $this->env->choiceService->replaceList(['questionId' => $choiceQuestion]));
        self::assertSame([], $this->choices($choiceQuestion));
    }

    public function testChoiceCrudAndMovingBetweenQuestionsKeepsCollectionsAndOrdering(): void
    {
        $q = $this->question('Choices');
        $other = $this->question('Other');
        $a = $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $q, 'label1' => 'A'])
        )->getId();
        $b = $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $q, 'label1' => 'B', 'position' => 1])
        )->getId();
        self::assertSame(['B', 'A'], array_column($this->choices($q), 'label1'));
        $this->env->choiceService->update($a, $this->env->choiceValidator->validate(['label1' => 'Updated A'], true));
        self::assertSame(['B', 'Updated A'], array_column($this->choices($q), 'label1'));
        $this->env->choiceService->update($a, $this->env->choiceValidator->validate(['position' => 0], true));
        self::assertSame(['Updated A', 'B'], array_column($this->choices($q), 'label1'));
        $this->env->choiceService->update($b, $this->env->choiceValidator->validate(['position' => 1], true));
        $this->env->choiceService->update($b, $this->env->choiceValidator->validate(['position' => 70000], true));
        self::assertSame(['Updated A', 'B'], array_column($this->choices($q), 'label1'));
        $this->env->choiceService->create(
            $this->env->choiceValidator->validate(['question' => $other, 'label1' => 'Other choice'])
        );
        $this->env->choiceService->update($b, $this->env->choiceValidator->validate(['question' => $other], true));
        self::assertSame([1], array_column($this->choices($q), 'position'));
        self::assertSame(['Other choice', 'B'], array_column($this->choices($other), 'label1'));
        self::assertSame([1, 2], array_column($this->choices($other), 'position'));
        self::assertSame($other, $this->env->choices->findById($b)->getQuestion()->getId());
        self::assertInstanceOf(\stdClass::class, $this->env->choiceService->getQuestionChoiceData($b)['question']);
        $this->env->manager->clear();
        self::assertCount(2, $this->env->questions->findById($other)->getQuestionChoices());
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
            fn () => $this->env->service->deleteList([$q, 999]),
            EntityNotFoundException::class
        );
        $this->reject(
            fn () => $this->env->service->update(
                $q,
                $this->env->validator->validate(['label1' => 'Invalid PUT'], false)
            )
        );
        $this->reject(
            fn () => $this->env->choiceService->create($this->env->choiceValidator->validate([
                'question' => 999,
                'label1' => 'Missing question',
            ]))->getId(),
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
            $this->env->choiceValidator->validate(['question' => $q, 'label1' => 'Answer', 'label4' => 'Fourth'])
        )->getId();
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

    public function testMultichoiceFiltersNormalizeBooleansAndLegacyIntegerValues(): void
    {
        $singleChoiceQuestion = $this->question('Single choice', ['multichoice' => 0]);
        $multiChoiceQuestion = $this->question('Multiple choices', ['multichoice' => 1]);
        foreach ([
            [0, false],
            [1, true],
            ['0', false],
            ['1', true],
            [false, false],
            [true, true],
            ['false', false],
            ['true', true],
        ] as [$value, $isMultiChoice]) {
            $params = (new GetQuestionsInputFormatter())->format(['multichoice' => $value]);
            self::assertSame($isMultiChoice, $params->getFilterFor('multichoice')['value']);
            self::assertSame(1, $this->env->service->getCount($params));
            self::assertSame(
                [$isMultiChoice ? $multiChoiceQuestion : $singleChoiceQuestion],
                array_column($this->env->service->getList($params), 'id')
            );
        }
    }

    /** @dataProvider invalidData */
    public function testInvalidScalarSemanticsAreRejected(array $data): void
    {
        $this->reject(
            fn () => $this->env->service->create(
                $this->env->validator->validate(array_merge([
                    'type' => Question::TYPE_SINGLE,
                    'multichoice' => 0,
                ], $data))
            )->getId()
        );
    }

    public static function invalidData(): array
    {
        return [[['type' => false]], [['type' => null]], [['multichoice' => 2]], [['position' => -1]],
            [['label1' => false]], [['label1' => str_repeat('x', 256)]]];
    }

    public function testDefaultLanguageIsRequiredAndOtherTranslationsUseTheSharedLabelRules(): void
    {
        foreach ([$this->env->validator, $this->env->choiceValidator] as $questionValidator) {
            $questionValidator->setDefaultLanguageIndex(3);
            $translation = 'Translation';
            $data = [
                'type' => Question::TYPE_SINGLE,
                'multichoice' => 0,
                'question' => 1,
                'label3' => '  Active language  ',
                'label1' => '  ' . $translation . '  '
            ];
            $validated = $questionValidator->validate($data);
            self::assertSame('Active language', $validated['label3']);
            self::assertSame($translation, $validated['label1']);
            $this->reject(fn () => $questionValidator->validate(array_replace($data, [
                'label1' => str_repeat('x', 256),
            ])));
            $activeLanguageOnly = $questionValidator->validate(array_diff_key($data, ['label1' => true]));
            self::assertSame('Active language', $activeLanguageOnly['label3']);
            unset($data['label3']);
            $this->reject(fn () => $questionValidator->validate($data));
            $this->reject(fn () => $questionValidator->validate($data + ['label3' => '   ']));
            self::assertSame([], $questionValidator->validate([], true));
        }
    }

    public function testGlobalDefinitionsIgnoreAnalysisFieldsFromThePayload(): void
    {
        $id = $this->question('Global', ['anr' => 22, 'mode' => 1, 'response' => []]);
        self::assertSame('Global', $this->env->service->getQuestionData($id)['label1']);
        self::assertArrayNotHasKey('anr', $this->env->service->getQuestionData($id));
        self::assertArrayNotHasKey('response', $this->env->service->getQuestionData($id));
    }

    /** @dataProvider invalidQueries */
    public function testQueryFieldsAreConstrained(array $query): void
    {
        $this->reject(fn () => (new GetQuestionsInputFormatter())->format($query));
    }

    public static function invalidQueries(): array
    {
        return [[['order' => 'position;DROP TABLE questions']], [['order' => ['id']]], [['filter' => []]],
            [['limit' => -1]], [['type' => 4]]];
    }

    private function question(string $label, array $data = []): int
    {
        return $this->env->service->create(
            $this->env->validator->validate(array_merge(['label1' => $label, 'type' => 2, 'multichoice' => 0], $data))
        )->getId();
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
