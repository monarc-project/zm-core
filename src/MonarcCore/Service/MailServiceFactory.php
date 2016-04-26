<?php
namespace MonarcCore\Service;

class MailServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'config'=> 'Config',
        'mimePart'=> 'MonarcCore\Service\Mime\Part',
        'mimeMessage' => 'MonarcCore\Service\Mime\Message',
        'mailMessage' => 'MonarcCore\Service\Mail\Message',
        'mailTransportSmtp' => 'MonarcCore\Service\Mail\Transport\Smtp',
        'mailTransportSmtpOptions' => 'MonarcCore\Service\Mail\Transport\SmtpOptions'
    );

}
