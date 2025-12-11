<?php

namespace System\Web\Page\Control\Event;

class TEventArgs
{
    public function __get($name): mixed
    {
        return $this->$name;
    }

    public function __set($name, $value): void
    {
        $this->$name = $value;
    }
}
