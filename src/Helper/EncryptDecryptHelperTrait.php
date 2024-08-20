<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Helper;

/** Is used to in the export/import functionality to protect the data. */
trait EncryptDecryptHelperTrait
{
    /**
     * @param string $data The data to encrypt.
     * @param string $key The key to use to encrypt the data.
     *
     * @return string|false The encrypted data or false if fails.
     */
    protected function encrypt(string $data, string $key)
    {
        return @openssl_encrypt($data, 'AES-256-CBC', hash('sha512', $key));
    }

    /**
     * @param string $data The data to decrypt.
     * @param string $key The key to use to decrypt the data.
     *
     * @return string|false The decrypted data.
     */
    protected function decrypt(string $data, string $key)
    {
        $decrypted = openssl_decrypt($data, 'AES-256-CBC', hash('sha512', $key));
        if ($decrypted === false) {
            $decrypted = openssl_decrypt($data, 'AES-256-ECB', hash('md5', $key));
        }

        return $decrypted;
    }
}
