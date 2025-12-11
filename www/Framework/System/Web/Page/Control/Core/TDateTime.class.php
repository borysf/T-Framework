<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;

class TDateTime extends TLiteral {
    
    #[Prop, Stateful]
    public string $format = 'r';

    #[Prop, Stateful]
    public ?int $timestamp = null;

    protected function onRender(?TEventArgs $e) : void {
        $this->text = date($this->format, $this->timestamp === null ? time() : $this->timestamp);
    }
}
