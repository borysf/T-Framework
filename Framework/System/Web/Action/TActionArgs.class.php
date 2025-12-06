<?php

namespace System\Web\Action;

class TActionArgs
{
    public function __construct(array|TActionArgs $args)
    {
        $args = is_object($args) ? get_object_vars($args) : $args;

        foreach ($args as $k => $v) {
            $this->$k = $v;
        }
    }

    public function __get($name): mixed
    {
        return $this->$name;
    }

    public function __set($name, $value): void
    {
        $this->$name = $value;
    }
}
