<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Historical Service Factory
 *
 * Class HistoricalServiceFactory
 * @package MonarcCore\Service
 */
class HistoricalServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\HistoricalTable',
        'entity' => 'MonarcCore\Model\Entity\Historical',
    ];
}