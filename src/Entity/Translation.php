<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="translations",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="type_key_lang_unq", columns={"type", "key", "lang"})
 *   },
 *   indexes={
 *    @ORM\Index(name="type_key_indx", columns={"type", "key"})
 *  }
 * )
 * @ORM\Entity
 */
class Translation extends TranslationSuperClass
{
}
