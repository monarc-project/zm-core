<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Theme Service Factory
 *
 * Class ThemeServiceFactory
 * @package Monarc\Core\Service
 */
class ThemeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\ThemeTable',
        'entity' => 'Monarc\Core\Model\Entity\Theme',
    ];
}
