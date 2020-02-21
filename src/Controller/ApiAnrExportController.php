<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Service\AnrService;
use Laminas\Mvc\Controller\AbstractRestfulController;

/**
 * Api Anr Export Controller
 *
 * Class ApiAnrExportController
 * @package Monarc\Core\Controller
 */
class ApiAnrExportController extends AbstractRestfulController
{
    /** @var AnrService */
    private $anrService;

    public function __construct(AnrService $anrService)
    {
        $this->anrService = $anrService;
    }

    public function create($data)
    {
        $output = $this->anrService->exportAnr($data);

        $contentType = 'application/json; charset=utf-8';
        $extension = '.json';
        if (!empty($data['password'])) {
            $contentType = 'text/plain; charset=utf-8';
            $extension = '.bin';
        }

        $this->getResponse()
            ->getHeaders()
            ->clearHeaders()
            ->addHeaderLine('Content-Type', $contentType)
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' .
                (empty($data['filename']) ? $data['id'] : $data['filename']) . $extension . '"');

        $this->getResponse()
            ->setContent($output);

        return $this->getResponse();
    }
}
