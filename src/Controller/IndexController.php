<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;

/**
 * Index Controller to load the Homepage. This is pretty much the only non-REST-API page since the app in an SPA, which
 * means all the browser routing is done client-side by the frontend code.
 */
class IndexController extends AbstractRestfulController
{
    /**
     * Default action route /, return template with JS
     * @return IndexController
     */
    public function indexAction()
    {
        return $this;
    }
}

