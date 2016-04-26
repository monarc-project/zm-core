<?php
namespace MonarcCore\Validator;

use Zend\I18n\Validator\Alnum;
use Zend\Validator\AbstractValidator;

/**
 * Alnum Multi Language
 *
 * @package MonarcCore\Validator
 * @author Jerome De Almeida <jerome.dealmeida@vesperiagroup.com>
 */
class AlnumMultiLanguage extends AbstractValidator
{
    const REQUIRED = 'required';
    const NOTALNUM = 'notalnum';

    protected $messageTemplates = array(
        self::REQUIRED => "one field is required",
        self::NOTALNUM => "the field must be alphanumeric",
    );

    /**
     * Options for the Alnum MultiLanguage validator
     *
     * @var array
     */
    protected $options = [
        'label' => '',
    ];

    /**
     * Returns the label option
     *
     * @return mixed
     */
    public function getLabel()
    {
        return $this->options['label'];
    }

    /**
     * Sets the label option
     *
     * @param  mixed $label
     * @return Between Provides a fluent interface
     */
    public function setLabel($label)
    {
        $this->options['label'] = $label;
        return $this;
    }

    /**
     * IsValid
     *
     * @param mixed $array
     * @return bool
     */
    public function isValid($array)
    {
        $label = $this->getLabel();

        $arrayLabel = [];
        for ($i=1; $i<=4; $i++) {
          $arrayLabel[] = $label . $i;
        }

        //verify existence of one label minimum
        $exist = false;
        $fieldToTest = [];
        foreach($array as $key => $value) {
            if (in_array($key, $arrayLabel)) {
                $exist = true;
                $fieldToTest[] = $key;
            }
        }

        //verify label is valid
        $isValid = false;
        if ($exist) {
            $alnumValidator = new Alnum(array('allowWhiteSpace' => true));
            foreach($fieldToTest as $field) {
                if (! $alnumValidator->isValid($field)) {
                    $isValid = false;
                    break;
                } else {
                    $isValid = true;
                }
            }
        } else {
           $isValid = false;
        }


        return $isValid;
    }
}

