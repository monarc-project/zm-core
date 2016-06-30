<?php
namespace MonarcCore\Service;

class MailService extends AbstractService
{
    protected $config;
    protected $mimePart;
    protected $mimeMessage;
    protected $mailMessage;
    protected $mailTransportSmtp;
    protected $mailTransportSmtpOptions;

    /**
     * Send
     *
     * @return \Zend\Db\ResultSet\ResultSet
     * @throws \Exception
     */
    public function send($email, $subject, $message) {


        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'Reply-To: Cases <info@cases.lu>' . "\r\n";
        $headers .= 'To: ' . $email . ' <' . $email .' >' . "\r\n";
        $headers .= 'From: Cases <info@cases.lu>' . "\r\n";

        mail($email, $subject, $message, $headers);


        /*

        $html = $this->mimePart;
        $html->type = "text/html";

        $body = $this->mimeMessage;

        $options = $this->mailTransportSmtpOptions;
        $options->setFromArray($this->config['smtp']);


        $html->setContent($message);

        $body->setParts(array($html));

        $mailMessage = $this->mailMessage;
        $mailMessage->addFrom($this->config['cases']['mail'], $this->config['cases']['name'])
            ->addTo($email)
            ->setSubject($subject)
            ->setBody($body);


        //smtp transport
        $transport = $this->mailTransportSmtp;
        $transport->setOptions($options);
        $transport->send($mailMessage);

        //echo $mailMessage->toString();
        */
    }
}