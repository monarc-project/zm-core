<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

/**
 * Api Anr Export Controller
 *
 * Class ApiAnrExportController
 * @package Monarc\Core\Controller
 */
class ApiAnrExportController extends AbstractController
{
    /**
     * @inheritdoc
     */
	public function get($id){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function getList(){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function create($data){
        $output = $this->getService()->exportAnr($data);

				if (empty($data['password'])) {
          $contentType = 'application/json; charset=utf-8';
          $extension = '.json';
        } else {
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

    /**
     * @inheritdoc
     */
	public function delete($id){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function deleteList($data){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function update($id, $data){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function patch($id, $data){
		$this->methodNotAllowed();
	}
}
