<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\QuestionChoice;
use Monarc\Core\Entity\QuestionChoiceSuperClass;
use Monarc\Core\Entity\QuestionSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\QuestionTable;
use Monarc\Core\Table\QuestionChoiceTable;

class QuestionChoiceService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private QuestionChoiceTable $questionChoiceTable,
        private QuestionTable $questionTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params, ?AnrSuperClass $anr = null): array
    {
        $params = $this->choiceParams($params, $anr);
        return array_map(
            fn ($choice): array => $this->serialize($choice),
            $this->questionChoiceTable->findByParams($params)
        );
    }

    public function getCount(FormattedInputParams $params, ?AnrSuperClass $anr = null): int
    {
        return $this->questionChoiceTable->countByParams($this->choiceParams($params, $anr));
    }

    public function getQuestionChoiceData(int $id, ?AnrSuperClass $anr = null): array
    {
        return $this->serialize($this->questionChoiceTable->findById($id));
    }

    public function serialize(QuestionChoiceSuperClass $choice): array
    {
        $questionChoiceData = array_merge(['id' => $choice->getId()], $choice->getLabels(), [
            'position' => $choice->getPosition(), 'creator' => $choice->getCreator(),
            'createdAt' => $choice->getCreatedAt(), 'updater' => $choice->getUpdater(),
            'updatedAt' => $choice->getUpdatedAt(),
            'question' => new \stdClass(),
        ]);

        return $questionChoiceData;
    }

    public function create(array $data, ?AnrSuperClass $anr = null): int
    {
        $question = $this->requireQuestion($data['question'], $anr);

        $choice = new QuestionChoice();
        $choice->setCreator($this->connectedUser->getEmail());
        $this->placeChoice($choice, $this->questionChoiceTable->findChoicesOfQuestion($question), $data, true);
        $choice->setQuestion($question)->setLabels($data);

        $this->questionChoiceTable->save($choice);

        return $choice->getId();
    }

    public function update(int $id, array $data, ?AnrSuperClass $anr = null): void
    {
        $choice = $this->questionChoiceTable->findById($id);
        $oldQuestion = $choice->getQuestion();
        $question = $this->requireQuestion($data['question'] ?? $oldQuestion->getId(), $anr);
        $this->placeChoice($choice, $this->questionChoiceTable->findChoicesOfQuestion($question), $data);
        $choice->setQuestion($question)->setLabels($data)->setUpdater($this->connectedUser->getEmail());
        if ($oldQuestion !== $question) {
            $this->numberChoices(array_values(array_filter(
                $this->questionChoiceTable->findChoicesOfQuestion($oldQuestion),
                fn ($sibling): bool => $sibling !== $choice
            )));
        }

        $this->questionChoiceTable->save($choice);
    }

    public function replaceList(array $data, ?AnrSuperClass $anr = null): void
    {
        $question = $this->requireQuestion($data['questionId'], $anr);
        // Resolve the parent and prepare the validated choices before scheduling any removal.
        $newChoices = [];
        foreach ($data['choice'] as $row) {
            $newChoices[] = (new QuestionChoice())->setLabels($row)->setPosition($row['position'])
                ->setCreator($this->connectedUser->getEmail());
        }
        $this->questionChoiceTable->beginTransaction();
        try {
            foreach ($this->questionChoiceTable->findChoicesOfQuestion($question) as $choice) {
                $question->removeQuestionChoice($choice);
                $this->questionChoiceTable->remove($choice, false);
            }
            foreach ($newChoices as $choice) {
                $choice->setQuestion($question);
                $this->questionChoiceTable->save($choice, false);
            }

            $this->questionChoiceTable->flush();
            $this->questionChoiceTable->commit();
        } catch (\Throwable $error) {
            $this->questionChoiceTable->rollback();
            throw $error;
        }
    }

    public function delete(int $id, ?AnrSuperClass $anr = null): void
    {
        $this->deleteList([$id], $anr);
    }

    public function deleteList(array $ids, ?AnrSuperClass $anr = null): void
    {
        $choices = array_map(fn ($id) => $this->questionChoiceTable->findById($id), $ids);
        foreach ($choices as $choice) {
            $question = $choice->getQuestion();
            $question->removeQuestionChoice($choice);
            $this->numberChoices(array_values(array_filter(
                $this->questionChoiceTable->findChoicesOfQuestion($question),
                fn ($sibling): bool => !in_array($sibling->getId(), $ids, true)
            )));

            $this->questionChoiceTable->remove($choice, false);
        }
        $this->questionChoiceTable->flush();
    }

    private function choiceParams(FormattedInputParams $params, ?AnrSuperClass $anr): FormattedInputParams
    {
        $params = clone $params;

        if ($params->hasFilterFor('question')) {
            $this->questionTable->findById((int)$params->getFilterFor('question')['value']);
        }
        return $params;
    }

    private function requireQuestion(int $id, ?AnrSuperClass $anr): QuestionSuperClass
    {
        $question = $this->questionTable->findById($id);
        if ($question->getType() !== 2) {
            throw new Exception('Choices require a choice question', 412);
        }
        return $question;
    }

    private function numberChoices(array $questionChoices): void
    {
        foreach ($questionChoices as $index => $questionChoice) {
            $questionChoice->setPosition($index + 1);
        }
    }

    private function placeChoice(
        QuestionChoiceSuperClass $questionChoice,
        array $siblings,
        array $data,
        bool $isNew = false
    ): void {
        $siblings = array_values(array_filter($siblings, fn ($sibling): bool => $sibling !== $questionChoice));
        if (count($siblings) >= 65535) {
            throw new Exception('Too many records', 412);
        }
        $position = $data['position'] ?? ($isNew ? count($siblings) + 1 : max(1, $questionChoice->getPosition()));
        $position = max(1, $position);
        array_splice($siblings, min($position, count($siblings) + 1) - 1, 0, [$questionChoice]);
        $this->numberChoices($siblings);
    }
}
