<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\EventListener;

use Laminas\Diactoros\ServerRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Parameters;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Request\Psr7Bridge\RequestConverter;
use RequestParseBodyException;

use function Laminas\Diactoros\normalizeUploadedFiles;

/** Parse multipart PUT/PATCH before the MVC-to-PSR-7 bridge reads php://input. */
class MultipartBodyListener
{
    public function __invoke(MvcEvent $event): void
    {
        $request = $event->getRequest();
        $contentType = $request->getHeader('Content-Type');
        if (!in_array($request->getMethod(), ['PUT', 'PATCH'], true)
            || !str_starts_with(
                strtolower($contentType ? $contentType->getFieldValue() : ''),
                'multipart/form-data'
            )
        ) {
            return;
        }
        try {
            [$data, $files] = request_parse_body();
        } catch (RequestParseBodyException $error) {
            throw new Exception(json_encode([
                'validationErrors' => ['body' => ['Invalid multipart request body']],
            ], JSON_THROW_ON_ERROR), 400, $error);
        }
        $request->setPost(new Parameters($data));
        $parsedRequest = RequestConverter::toLaminas(new ServerRequest([], normalizeUploadedFiles($files)));
        $request->setFiles($parsedRequest->getFiles());
        // The bridge receives the parsed parameters and must not read the consumed raw body.
        $request->setContent('');
    }
}
