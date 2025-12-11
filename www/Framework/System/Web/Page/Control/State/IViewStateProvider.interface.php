<?php
namespace System\Web\Page\Control\State;

interface IViewStateProvider {
    public function write(mixed $state) : string;
    public function read(string $state) : mixed;
}