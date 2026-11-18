<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;
use ZipArchive;

/** Validate all languages before the service moves any uploaded file. */
class DeliveryModelUpload extends AbstractValidator
{
    private const INVALID = 'invalidTemplate';

    protected $messageTemplates = [self::INVALID => 'Select a readable DOCX document for each uploaded language.'];

    public function isValid($value)
    {
        if (!is_array($value)) {
            $this->error(self::INVALID);

            return false;
        }
        foreach ($value as $language => $file) {
            if (!in_array($language, [1, 2, 3, 4], true)
                || !is_array($file)
                || ($file['error'] ?? null) !== UPLOAD_ERR_OK
                || !is_string($file['name'] ?? null)
                || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'docx'
                || !is_string($file['tmp_name'] ?? null)
                || !is_file($file['tmp_name'])
                || !is_readable($file['tmp_name'])
                || ($file['size'] ?? 0) <= 0
                || filesize($file['tmp_name']) !== $file['size']
            ) {
                $this->error(self::INVALID);

                return false;
            }
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) {
                $this->error(self::INVALID);

                return false;
            }
            $valid = $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName('word/document.xml') !== false;
            $zip->close();
            if (!$valid) {
                $this->error(self::INVALID);

                return false;
            }
        }

        return true;
    }
}
