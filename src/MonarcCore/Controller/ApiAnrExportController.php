<?php
namespace MonarcCore\Controller;

/**
 * Api Anr Export Controller
 *
 * Class ApiAnrExportController
 * @package MonarcCore\Controller
 */
class ApiAnrExportController extends AbstractController
{
	public function get($id){
		$this->methodNotAllowed();
	}

	public function getList(){
		$this->methodNotAllowed();
	}
	public function create($data){
        $output = $this->getService()->exportAnr($data);

        $response = $this->getResponse();
        $response->setContent($output);

        $headers = $response->getHeaders();
        $headers->clearHeaders()
            ->addHeaderLine('Content-Type', 'text/plain; charset=utf-8')
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . (empty($data['filename'])?$data['id']:$data['filename']) . '.bin"');

        return $this->response;
	}
	public function delete($id){
		$this->methodNotAllowed($id);
	}
	public function deleteList($data){
		$this->methodNotAllowed();
	}
	public function update($id, $data){
		$this->methodNotAllowed();
	}
	public function patch($id, $data){
		$this->methodNotAllowed();
	}
}