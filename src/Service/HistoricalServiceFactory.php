<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Historical Service Factory
 *
 * Class HistoricalServiceFactory
 * @package Monarc\Core\Service
 */
class HistoricalServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\HistoricalTable',
        'entity' => 'Monarc\Core\Entity\Historical',
    ];
}
