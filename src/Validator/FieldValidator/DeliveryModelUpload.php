<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;
use ZipArchive;

/** Check the DOCX package after the standard file validators have passed. */
class DeliveryModelUpload extends AbstractValidator
{
    private const INVALID = 'invalidTemplate';

    protected $messageTemplates = [self::INVALID => 'Select a readable DOCX document for each uploaded language.'];

    public function isValid($value)
    {
        $zip = new ZipArchive();
        if ($zip->open($value['tmp_name']) === true) {
            $valid = $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName('word/document.xml') !== false;
            $zip->close();
            if ($valid) {
                return true;
            }
        }

        $this->error(self::INVALID);

        return false;
    }
}
