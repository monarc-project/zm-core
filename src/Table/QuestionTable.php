<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\Question;

class QuestionTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = Question::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /** Ordered global definitions. */
    public function findOrderedQuestions(): array
    {
        return $this->getRepository()->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }
}
