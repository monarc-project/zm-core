<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\Question;
use Monarc\Core\Entity\QuestionSuperClass;
use Monarc\Core\Entity\QuestionChoiceSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\QuestionTable;
use Monarc\Core\Table\QuestionChoiceTable;

class QuestionService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private QuestionTable $questionTable,
        private QuestionChoiceTable $questionChoiceTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params, ?AnrSuperClass $anr = null): array
    {
        return array_map(
            fn ($question): array => $this->serialize($question, true),
            $this->questionTable->findByParams($params)
        );
    }

    public function getCount(FormattedInputParams $params, ?AnrSuperClass $anr = null): int
    {
        return $this->questionTable->countByParams($params);
    }

    public function getQuestionData(int $id, ?AnrSuperClass $anr = null): array
    {
        return $this->serialize($this->questionTable->findById($id));
    }

    public function serialize(QuestionSuperClass $question, bool $withChoices = false): array
    {
        $questionData = array_merge($this->content($question), [
            'type' => $question->getType(), 'multichoice' => (int)$question->isMultiChoice(),
            // Legacy JSON represented the collection as an object; choices is the populated list field.
            'questionChoices' => new \stdClass(),
        ]);

        if ($withChoices && $question->getType() === 2) {
            $questionData['choices'] = array_map(function ($choice): array {
                return array_merge($this->content($choice), ['question' => new \stdClass()]);
            }, $this->questionChoiceTable->findChoicesOfQuestion($question));
        }

        return $questionData;
    }

    public function create(array $data, ?AnrSuperClass $anr = null): int
    {
        $question = new Question();
        $siblings = $this->questionTable->findOrderedQuestions($anr);
        $this->validatePlacement($data, $anr);

        $this->applyData($question, $data);
        $question->setCreator($this->connectedUser->getEmail());
        $this->placeQuestion($question, $siblings, $data, true);
        $this->questionTable->save($question);

        return $question->getId();
    }

    public function update(int $id, array $data, ?AnrSuperClass $anr = null): void
    {
        $question = $this->questionTable->findById($id);

        $this->validatePlacement($data, $anr, $question);
        $this->placeQuestion($question, $this->questionTable->findOrderedQuestions($anr), $data);
        $this->applyData($question, $data);
        $question->setUpdater($this->connectedUser->getEmail());

        $this->questionTable->save($question);
    }

    public function delete(int $id, ?AnrSuperClass $anr = null): void
    {
        $this->deleteList([$id], $anr);
    }

    public function deleteList(array $ids, ?AnrSuperClass $anr = null): void
    {
        $questions = array_map(fn ($id) => $this->questionTable->findById($id), $ids);
        foreach ($questions as $question) {
            foreach ($this->questionChoiceTable->findChoicesOfQuestion($question) as $choice) {
                $question->removeQuestionChoice($choice);
                $this->questionChoiceTable->remove($choice, false);
            }
            $this->questionTable->remove($question, false);
        }

        $this->numberQuestions(array_values(array_filter(
            $this->questionTable->findOrderedQuestions($anr),
            fn ($question): bool => !in_array($question->getId(), $ids, true)
        )));
        $this->questionTable->flush();
    }

    private function applyData(QuestionSuperClass $question, array $data): void
    {
        $question->setLabels($data);
        if (isset($data['type'])) {
            $question->setType($data['type']);
        }
        if (isset($data['multichoice'])) {
            $question->setIsMultiChoice((bool)$data['multichoice']);
        }
    }

    private function validatePlacement(array $data, ?AnrSuperClass $anr, ?QuestionSuperClass $question = null): void
    {
        if (($data['implicitPosition'] ?? null) === 3) {
            $previous = $this->questionTable->findById($data['previous'] ?? 0);
            if ($previous === $question) {
                throw new Exception('Invalid previous question', 412);
            }
        }
    }

    private function content(QuestionSuperClass|QuestionChoiceSuperClass $questionOrChoice): array
    {
        $questionOrChoiceData = array_merge(['id' => $questionOrChoice->getId()], $questionOrChoice->getLabels(), [
            'position' => $questionOrChoice->getPosition(), 'creator' => $questionOrChoice->getCreator(),
            'createdAt' => $questionOrChoice->getCreatedAt(), 'updater' => $questionOrChoice->getUpdater(),
            'updatedAt' => $questionOrChoice->getUpdatedAt(),
        ]);

        return $questionOrChoiceData;
    }

    private function numberQuestions(array $questions): void
    {
        foreach ($questions as $index => $question) {
            $question->setPosition($index + 1);
        }
    }

    private function placeQuestion(
        QuestionSuperClass $question,
        array $siblings,
        array $data,
        bool $isNew = false
    ): void {
        $siblings = array_values(array_filter($siblings, fn ($sibling): bool => $sibling !== $question));
        if (count($siblings) >= 65535) {
            throw new Exception('Too many records', 412);
        }
        $position = $isNew ? count($siblings) + 1 : max(1, $question->getPosition());
        if (($data['implicitPosition'] ?? null) === 1) {
            $position = 1;
        } elseif (($data['implicitPosition'] ?? null) === 2) {
            $position = count($siblings) + 1;
        } elseif (($data['implicitPosition'] ?? null) === 3) {
            $position = null;
            foreach ($siblings as $index => $sibling) {
                if ($sibling->getId() === ($data['previous'] ?? null)) {
                    $position = $index + 2;
                }
            }
            if ($position === null) {
                throw new Exception('Previous record must belong to the same scope', 412);
            }
        } elseif (isset($data['position'])) {
            $position = max(1, $data['position']);
        }
        array_splice($siblings, min($position, count($siblings) + 1) - 1, 0, [$question]);

        $this->numberQuestions($siblings);
    }
}
