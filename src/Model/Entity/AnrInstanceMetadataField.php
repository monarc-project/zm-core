<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\LabelTranslationKeyEntityTrait;

/**
 * @ORM\Table(name="anr_instance_metadata_fields", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class AnrInstanceMetadataField extends AnrInstanceMetadataFieldSuperClass
{
    use LabelTranslationKeyEntityTrait;

    public static function constructFromObject(AnrInstanceMetadataField $anrInstanceMetadataField): self
    {
        return (new self())->setLabelTranslationKey($anrInstanceMetadataField->getLabelTranslationKey());
    }
}
