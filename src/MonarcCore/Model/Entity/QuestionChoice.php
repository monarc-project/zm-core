<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

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