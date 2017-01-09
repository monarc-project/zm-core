<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Question's choices
 *
 * @ORM\Table(name="questions_choices", indexes={
 *      @ORM\Index(name="question_id", columns={"question_id"}),
 * })
 * @ORM\Entity
 */
class QuestionChoice extends QuestionChoiceSuperclass
{
}

