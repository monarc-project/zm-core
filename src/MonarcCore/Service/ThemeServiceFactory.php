<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Theme Service Factory
 *
 * Class ThemeServiceFactory
 * @package MonarcCore\Service
 */
class ThemeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ThemeTable',
        'entity' => 'MonarcCore\Model\Entity\Theme',
    ];
}