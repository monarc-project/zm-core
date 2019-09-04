<?php
namespace Monarc\Core\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Console\Request as ConsoleRequest;
use Zend\Console\ColorInterface;

class MailTesterService {
    protected $serviceLocator;
    protected $console;
    protected $mailService;

    public function __construct(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;
        $this->console = $serviceLocator->get('console');
        $this->mailService = $serviceLocator->get('Monarc\Core\Service\MailService');
    }

    public function send(ConsoleRequest $request){
        $email = trim($request->getParam('email'));
        if(empty($email)){
            $this->console->write("Email is empty\n",ColorInterface::RED);
            return false;
        }
        $validator = new \Zend\Validator\EmailAddress();
        if(!$validator->isValid($email)){
            $this->console->write("Email is not valid:\n",ColorInterface::RED);
            foreach ($validator->getMessages() as $message) {
                $this->console->write("\t- $message\n");
            }
            return false;
        }

        $from = trim($request->getParam('from'));
        if(!empty($from)){
            if(!$validator->isValid($from)){
                $this->console->write("From email is not valid:\n",ColorInterface::RED);
                foreach ($validator->getMessages() as $message) {
                    $this->console->write("\t- $message\n");
                }
                $from = null;
            }
        }
        if(empty($from)){
            $this->mailService->send($email,
                '[Monarc] Test email / NOT REPLY',
                '[Monarc] Test email / NOT REPLY',
                'info@monarc.lu');
        }else{
            $this->mailService->send($email,
                '[Monarc] Test email / NOT REPLY',
                '[Monarc] Test email / NOT REPLY',
                $from);
        }
    }
}
