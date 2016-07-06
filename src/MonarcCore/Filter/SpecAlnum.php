<?php

namespace MonarcCore\Filter;

use Locale;
use Traversable;
use \Zend\I18n\Filter\AbstractLocale;

class SpecAlnum extends AbstractLocale
{
    /**
     * @var array
     */
    protected $options = [
        'locale'            => null,
        'allow_white_space' => false,
        'allow_quotes'      => true
    ];

    /**
     * Sets default option values for this instance
     *
     * @param array|Traversable|bool|null $allowWhiteSpaceOrOptions
     * @param bool|null $allowQuotes
     * @param string|null $locale
     */
    public function __construct($allowWhiteSpaceOrOptions = null, $allowQuotes = null, $locale = null)
    {
        parent::__construct();
        if ($allowWhiteSpaceOrOptions !== null) {
            if (static::isOptions($allowWhiteSpaceOrOptions)) {
                $this->setOptions($allowWhiteSpaceOrOptions);
            } else {
                $this->setAllowWhiteSpace($allowWhiteSpaceOrOptions);
                $this->setLocale($locale);
            }
        }
    }

    /**
     * Sets the allowWhiteSpace option
     *
     * @param  bool $flag
     * @return SpecAlnum Provides a fluent interface
     */
    public function setAllowWhiteSpace($flag = true)
    {
        $this->options['allow_white_space'] = (bool) $flag;
        return $this;
    }

    /**
     * Whether white space is allowed
     *
     * @return bool
     */
    public function getAllowWhiteSpace()
    {
        return $this->options['allow_white_space'];
    }

    /**
     * Sets the allowQuotes option
     *
     * @param  bool $flag
     * @return SpecAlnum Provides a fluent interface
     */
    public function setAllowQuotes($flag = true)
    {
        $this->options['allow_quotes'] = (bool) $flag;
        return $this;
    }

    /**
     * Whether quotes are allowed
     *
     * @return bool
     */
    public function getAllowQuotes()
    {
        return $this->options['allow_quotes'];
    }

    /**
     * Defined by Zend\Filter\FilterInterface
     *
     * Returns $value as string with all non-alphanumeric characters removed
     *
     * @param  string|array $value
     * @return string|array
     */
    public function filter($value)
    {
        if (!is_scalar($value) && !is_array($value)) {
            return $value;
        }

        $whiteSpace = $this->options['allow_white_space'] ? '\s' : '';
        $quotes     = $this->options['allow_quotes'] ? '\'"' : '';
        $language   = Locale::getPrimaryLanguage($this->getLocale());

        if (!static::hasPcreUnicodeSupport()) {
            // POSIX named classes are not supported, use alternative a-zA-Z0-9 match
            $pattern = '/[^a-zA-Z0-9\\-_' . $whiteSpace . $quotes . ']/';
        } elseif ($language == 'ja'|| $language == 'ko' || $language == 'zh') {
            // Use english alphabet
            $pattern = '/[^a-zA-Z0-9\\-_'  . $whiteSpace . $quotes . ']/u';
        } else {
            // Use native language alphabet
            $pattern = '/[^\p{L}\p{N}\\-_' . $whiteSpace . $quotes . ']/u';
        }

        return preg_replace($pattern, '', $value);
    }
}
