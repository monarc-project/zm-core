<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

/**
 * Index Controller to load the Homepage. This is pretty much the only non-REST-API page since the app in an SPA, which
 * means all the browser routing is done client-side by the frontend code.
 * @package MonarcCore\Controller
 */
class IndexController extends AbstractController
{
    public function indexAction(){
        return $this;
    }
}

