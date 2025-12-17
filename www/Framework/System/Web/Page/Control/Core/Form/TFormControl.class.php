<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\State\TViewState;
use System\Web\TPostBackEventArgs;

trait TFormControl {
    protected function renderContents() : void {}

    /** INTERNAL USE ONLY. Sets automatic HTML name for the form field. */
    protected function setHtmlName() : void {
        $this->html->name = $this->createHtmlName();
    }

    protected function createHtmlName() : string {
        return !isset($this->html->name) 
            ? TViewState::FORM_CONTROL_HTML_NAME_PREFIX.'['.$this->getSystemId().'][]' 
            : $this->html->name;
    }

    /** Event fires when post back occures. */
    protected function onPostBack(?TPostBackEventArgs $args) : void {}

    /** Event fires when post back completes. */
    protected function onPostBackComplete(?TPostBackEventArgs $args) : void {}
}
