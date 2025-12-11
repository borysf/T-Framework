<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;

/**
 * Allows creating a control with custom HTML tag.
 */
class THtmlElement extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = null;

    /** HTML tag name */
    #[Prop, Stateful]
    public ?string $name = null;

    /** Whether to render enclosing tag (`</name>`) or render as self-closing tag (`<name />`) */
    #[Prop, Stateful]
    public bool $hasEndTag = true;

    protected function customTagName() : array {
        return [$this->name, $this->hasEndTag];
    }
}
