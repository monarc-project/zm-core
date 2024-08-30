<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Request;

use Laminas\Psr7Bridge\Laminas\Request as LaminasBridgeRequest;

class Request extends LaminasBridgeRequest
{
    private array $attributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttribute($attribute, $default = null)
    {
        if (!\array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    public function addAttribute(string $name, $value): self
    {
        if (!\array_key_exists($name, $this->attributes)) {
            $this->attributes[$name] = $value;
        }

        return $this;
    }
}
