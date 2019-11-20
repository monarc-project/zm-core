<?php
namespace Monarc\Core\Filter;

use Zend\Filter\AbstractFilter;

/**
 * Class Password is an implementation of AbstractFilter that automatically hashes the password using a secure
 * algorithm.
 * @package Monarc\Core\Filter
 */
class Password extends AbstractFilter
{
    public function filter($value)
    {
        if (!empty($value)) {
            $value = password_hash($value, PASSWORD_BCRYPT);
        }

        return $value;
    }
}
