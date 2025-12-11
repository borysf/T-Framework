<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;

/**
 * Displays a literal text. 
 */
class TLiteral extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const HTML_HAS_ATTRIBUTES = false;
    const CHILDREN_TYPES_ALLOW = false;

    /** Whether to encode HTML special chars. If set to `false`, then pure HTML is rendered as passed to `text` prop. */
    #[Prop, Stateful]
    public bool $encode = true;

    protected function renderContents() : void {
        echo $this->encode && $this->text ? htmlspecialchars($this->text) : $this->text;
    }
}