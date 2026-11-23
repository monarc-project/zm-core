<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\UserSuperClass;
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

    public function getList(FormattedInputParams $params): array
    {
        return array_map(
            fn ($questionChoice): array => $this->serialize($questionChoice),
            $this->questionChoiceTable->findByParams($params)
        );
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->questionChoiceTable->countByParams($params);
    }

    public function getQuestionChoiceData(int $id): array
    {
        return $this->serialize($this->questionChoiceTable->findById($id));
    }

    public function serialize(QuestionChoiceSuperClass $questionChoice): array
    {
        $questionChoiceData = array_merge(['id' => $questionChoice->getId()], $questionChoice->getLabels(), [
            'position' => $questionChoice->getPosition(),
            'creator' => $questionChoice->getCreator(),
            'createdAt' => $questionChoice->getCreatedAt(),
            'updater' => $questionChoice->getUpdater(),
            'updatedAt' => $questionChoice->getUpdatedAt(),
            'question' => new \stdClass(),
        ]);

        return $questionChoiceData;
    }

    public function create(array $data): QuestionChoice
    {
        $question = $this->requireQuestion($data['question']);

        $questionChoice = new QuestionChoice();
        $questionChoice->setCreator($this->connectedUser->getEmail());
        $this->placeChoice($questionChoice, $this->questionChoiceTable->findChoicesOfQuestion($question), $data);
        $questionChoice->setQuestion($question)
            ->setLabels($data);

        $this->questionChoiceTable->save($questionChoice);

        return $questionChoice;
    }

    public function update(int $id, array $data): void
    {
        $questionChoice = $this->questionChoiceTable->findById($id);
        $oldQuestion = $questionChoice->getQuestion();
        $question = $this->requireQuestion($data['question'] ?? $oldQuestion->getId());
        $this->placeChoice($questionChoice, $this->questionChoiceTable->findChoicesOfQuestion($question), $data);
        $questionChoice->setQuestion($question)
            ->setLabels($data)
            ->setUpdater($this->connectedUser->getEmail());
        if ($oldQuestion !== $question) {
            $this->numberChoices(array_values(array_filter(
                $this->questionChoiceTable->findChoicesOfQuestion($oldQuestion),
                fn ($sibling): bool => $sibling !== $questionChoice
            )));
        }

        $this->questionChoiceTable->save($questionChoice);
    }

    public function replaceList(array $data): void
    {
        $question = $this->questionTable->findById((int)$data['questionId']);
        if ($question->getType() !== QuestionSuperClass::TYPE_CHOICE) {
            return;
        }
        if (!is_array($data['choice'] ?? null)) {
            throw new Exception('Invalid choice list', 412);
        }
        $newChoices = [];
        foreach ($data['choice'] as $row) {
            $newChoices[] = (new QuestionChoice())
                ->setLabels($row)
                ->setPosition(count($newChoices) + 1)
                ->setCreator($this->connectedUser->getEmail());
        }
        $this->questionChoiceTable->beginTransaction();
        try {
            foreach ($this->questionChoiceTable->findChoicesOfQuestion($question) as $questionChoice) {
                $question->removeQuestionChoice($questionChoice);
                $this->questionChoiceTable->remove($questionChoice, false);
            }
            foreach ($newChoices as $questionChoice) {
                $questionChoice->setQuestion($question);
                $this->questionChoiceTable->save($questionChoice, false);
            }

            $this->questionChoiceTable->flush();
            $this->questionChoiceTable->commit();
        } catch (\Throwable $error) {
            $this->questionChoiceTable->rollback();
            throw $error;
        }
    }

    public function delete(int $id): void
    {
        $this->deleteList([$id]);
    }

    public function deleteList(array $ids): void
    {
        $questionChoices = array_map(fn ($id) => $this->questionChoiceTable->findById($id), $ids);
        foreach ($questionChoices as $questionChoice) {
            $question = $questionChoice->getQuestion();
            $question->removeQuestionChoice($questionChoice);
            $this->numberChoices(array_values(array_filter(
                $this->questionChoiceTable->findChoicesOfQuestion($question),
                fn ($sibling): bool => !in_array($sibling->getId(), $ids, true)
            )));

            $this->questionChoiceTable->remove($questionChoice, false);
        }
        $this->questionChoiceTable->flush();
    }

    private function requireQuestion(int $id): QuestionSuperClass
    {
        $question = $this->questionTable->findById($id);
        if ($question->getType() !== QuestionSuperClass::TYPE_CHOICE) {
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
        array $questionChoices,
        array $data
    ): void {
        $questionChoices = array_values(array_filter(
            $questionChoices,
            fn ($existingChoice): bool => $existingChoice !== $questionChoice
        ));
        $position = $data['position'] ?? ($questionChoice->getPosition() ?: count($questionChoices) + 1);
        array_splice($questionChoices, max(0, min($position - 1, count($questionChoices))), 0, [$questionChoice]);
        $this->numberChoices($questionChoices);
    }
}
