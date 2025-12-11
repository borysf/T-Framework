<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TRadio extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = 'input';
    const HTML_HAS_END_TAG = false;

    #[Prop, Stateful]
    public bool $checked = false;

    #[Prop, Stateful]
    public int|string|null $value = null;

    protected function onPostBack(?TPostBackEventArgs $args) : void {
        $this->checked = $args->value == $this->value;
    }

    protected function onRender(?TEventArgs $args) : void {
        $this->html->type = 'radio';
        $this->html->checked = $this->checked;
        $this->html->value = $this->value;
        $this->setHtmlName();
        parent::onRender($args);
    }
}
