<?php declare(strict_types=1);

namespace Monarc\Core\Model\Entity;

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
