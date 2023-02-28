<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Helper;

use Monarc\Core\Exception\Exception;

trait FileUploadHelperTrait
{
    public function moveTmpFile(array $tmpFile, $destinationPath, $filename): string
    {
        if ($tmpFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(
                sprintf('An error occurred during the file upload. Error code: %d', (int)$tmpFile['error'])
            );
        }

        if (!is_dir($destinationPath) || !is_writable($destinationPath)) {
            throw new Exception(
                sprintf('The files upload directory "%s" is doesn\'t exist or or not writable', $destinationPath)
            );
        }

        $filePathAndName = $destinationPath . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpFile['tmp_name'], $filePathAndName)) {
            throw new Exception(
                'The file cant be saved, please check if the destination directory exists and has write permissions.',
            );
        }

        return $filePathAndName;
    }
}
