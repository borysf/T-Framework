<?php
namespace System\Web\Page\Control\Core\Form\List;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;

class TListOption extends TControl {
    const HTML_TAG_NAME = 'option';
    const HTML_HAS_END_TAG = true;
    const CHILDREN_TYPES_ALLOW = [];

    #[Prop]
    public $selected = false;

    #[Prop]
    public $value;

    protected function renderContents() : void {
        echo htmlspecialchars($this->text);
    }

    protected function onRender(?TEventArgs $args) : void {
        $this->html->value = $this->value;
        $this->html->selected = $this->selected;
    }
}