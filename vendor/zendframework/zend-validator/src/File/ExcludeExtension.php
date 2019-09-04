<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Validator\File;

use Zend\Validator\File\FileInformationTrait;
use Zend\Validator\Exception;

/**
 * Validator for the excluding file extensions
 */
class ExcludeExtension extends Extension
{
    use FileInformationTrait;

    /**
     * @const string Error constants
     */
    const FALSE_EXTENSION = 'fileExcludeExtensionFalse';
    const NOT_FOUND       = 'fileExcludeExtensionNotFound';

    /**
     * @var array Error message templates
     */
    protected $messageTemplates = [
        self::FALSE_EXTENSION => "File has an incorrect extension",
        self::NOT_FOUND       => "File is not readable or does not exist",
    ];

    /**
     * Returns true if and only if the file extension of $value is not included in the
     * set extension list
     *
     * @param  string|array $value Real file to check for extension
     * @param  array        $file  File data from \Zend\File\Transfer\Transfer (optional)
     * @return bool
     */
    public function isValid($value, $file = null)
    {
        $fileInfo = $this->getFileInfo($value, $file);

        $this->setValue($fileInfo['filename']);

        // Is file readable ?
        if (empty($fileInfo['file']) || false === is_readable($fileInfo['file'])) {
            $this->error(self::NOT_FOUND);
            return false;
        }

        $extension  = substr($fileInfo['filename'], strrpos($fileInfo['filename'], '.') + 1);
        $extensions = $this->getExtension();

        if ($this->getCase() && (! in_array($extension, $extensions))) {
            return true;
        } elseif (! $this->getCase()) {
            foreach ($extensions as $ext) {
                if (strtolower($ext) == strtolower($extension)) {
                    $this->error(self::FALSE_EXTENSION);
                    return false;
                }
            }

            return true;
        }

        $this->error(self::FALSE_EXTENSION);
        return false;
    }
}
