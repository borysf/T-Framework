<?php
namespace System\Web\Page\Control\Template;

use System\Web\Page\Control\TControl;

class TTemplateLiteral extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const HTML_HAS_ATTRIBUTES = false;
    const CHILDREN_TYPES_ALLOW = false;

    public bool $stateful = false;

    public function __construct(array $props = [], array $children = []) {
        if (isset($props['text'])) {
            $this->text = &$props['text'];
        }
        $this->raise('onCreate');
    }

    protected function renderContents() : void {}

    protected function render() : void {
        echo $this->text;
    }
}