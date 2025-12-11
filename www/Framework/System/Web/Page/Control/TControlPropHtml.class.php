<?php

namespace System\Web\Page\Control;

use ArrayAccess;
use System\Debug\TDebug;

/**
 * Represents `TControl`'s `html` prop. All attributes set here
 * will be rendered as HTML attributes of the control's element.
 */
class TControlPropHtml implements ArrayAccess
{
    protected readonly TControlPropStyle $__style;
    protected readonly TControlPropClass $__class;
    protected array $__attributes = [];

    public function __construct()
    {
        $this->__style = new TControlPropStyle;
        $this->__class = new TControlPropClass;
    }

    public function __set($name, $value): void
    {
        if ($name == 'style') {
            $this->__style->setValue($value);
        } else if ($name == 'class') {
            $this->__class->setValue($value);
        } else {
            $this->__attributes[$name] = $value;
        }
    }

    public function __get($name): mixed
    {
        if ($name == 'style') {
            return $this->__style;
        } else if ($name == 'class') {
            return $this->__class;
        } else if (isset($this->__attributes[$name])) {
            return $this->__attributes[$name];
        }

        return null;
    }

    public function __isset($name): bool
    {
        return $name == 'style' || $name == 'class' || isset($this->__attributes[$name]);
    }

    public function __toString(): string
    {
        $result = '';

        $style = $this->__style->__toString();

        if ($style) {
            $result .= ' style="' . htmlspecialchars($style) . '"';
        }

        $class = $this->__class->__toString();

        if ($class) {
            $result .= ' class="' . htmlspecialchars($class) . '"';
        }

        foreach ($this->__attributes as $name => $value) {
            if ($value == '') {
                continue;
            }

            $result .= ' ' . $name . (!is_bool($value) ? '="' . htmlspecialchars($value) . '"' : '');
        }

        return $result;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetUnset($offset): void
    {
        if ($offset == 'style') {
            $this->__style->setValue('');
        } else if ($offset == 'class') {
            $this->__class->setValue('');
        } else if (isset($this->__attributes[$offset])) {
            unset($this->__attributes[$offset]);
        }
    }
}
