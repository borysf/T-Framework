<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class THiddenField extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = 'input';
    const HTML_HAS_END_TAG = false;

    protected function onRender(?TEventArgs $args) : void {
        $this->html->type = 'hidden';
        $this->html->value = $this->text;
        $this->setHtmlName();

        parent::onRender($args);
    }

    protected function onPostBack(?TPostBackEventArgs $args) : void {
        if ($args->value === null) {
            return;
        }
        $this->text = $args->value[0];
    }
}