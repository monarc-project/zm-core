<?php
namespace Monarc\Core\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;

use Zend\Console\Exception\RuntimeException as ConsoleException;

use \Monarc\Core\Service\MailTesterService;

class MailTesterController extends AbstractActionController
{
    protected $MailTesterService;

    public function __construct(MailTesterService $MailTesterService){
        $this->MailTesterService = $MailTesterService;
    }

    public function indexAction(){
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException('You can only use this action from a console!');
        }
        try {
            $this->MailTesterService->send($request);
        } catch (ConsoleException $e) {
            // Could not get console adapter - most likely we are not running inside a console window.
        }
    }
}
