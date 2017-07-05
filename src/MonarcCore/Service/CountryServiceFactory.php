<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Country Service Factory
 *
 * Class CountryServiceFactory
 * @package MonarcCore\Service
 */
class CountryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\CountryTable',
        'entity' => 'MonarcCore\Model\Entity\Country',
    ];
}