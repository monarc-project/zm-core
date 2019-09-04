<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */
namespace Monarc\Core\Model;

/**
 * Class DbCli
 * TODO: Its a temporary solution to allow usage of autowiring.
 * We need to refactor the extended DB class and factory. 1. create and interface. 2. define different implementations of it and factory create an object based on it.
 *
 * @package Monarc\Core\Model
 */
class DbCli extends Db {}
