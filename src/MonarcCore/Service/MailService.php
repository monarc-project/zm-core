<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Mail Service
 *
 * Class MailService
 * @package MonarcCore\Service
 */
class MailService extends AbstractService
{
    /**
     * Send an email to the specified recipient, with the subject and message set
     * @param string $email Email address
     * @param string $subject Email subject
     * @param string $message Email message
     */
    public function send($email, $subject, $message)
    {
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'Reply-To: Cases <info@cases.lu>' . "\r\n";
        $headers .= 'From: Cases <info@cases.lu>' . "\r\n";

        // TODO: don't exist mail class in zf2?
        mail($email, $subject, $message, $headers);
    }
}