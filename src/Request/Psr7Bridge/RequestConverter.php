<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Request\Psr7Bridge;

use Monarc\Core\Request\Request;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class RequestConverter
{
    public static function toLaminas(ServerRequestInterface $psr7Request): Request
    {
        $laminasRequest = new Request(
            $psr7Request->getMethod(),
            $psr7Request->getUri(),
            $psr7Request->getHeaders(),
            $psr7Request->getCookieParams(),
            $psr7Request->getQueryParams(),
            $psr7Request->getParsedBody() ?: [],
            self::convertUploadedFiles($psr7Request->getUploadedFiles()),
            $psr7Request->getServerParams()
        );
        $laminasRequest->setContent($psr7Request->getBody());
        $laminasRequest->setAttributes($psr7Request->getAttributes());

        return $laminasRequest;
    }

    /**
     * Convert a PSR-7 uploaded files structure to a $_FILES structure
     *
     * @param UploadedFileInterface[]
     *
     * @return array
     */
    private static function convertUploadedFiles(array $uploadedFiles): array
    {
        $files = [];
        foreach ($uploadedFiles as $name => $upload) {
            if (\is_array($upload)) {
                $files[$name] = self::convertUploadedFiles($upload);
                continue;
            }

            $uploadError = $upload->getError();
            $isUploadError = $uploadError !== UPLOAD_ERR_OK;

            $files[$name] = [
                'name' => $upload->getClientFilename(),
                'type' => $upload->getClientMediaType(),
                'size' => $upload->getSize(),
                'tmp_name' => !$isUploadError ? $upload->getStream()->getMetadata('uri') : '',
                'error' => $uploadError,
            ];
        }

        return $files;
    }
}
