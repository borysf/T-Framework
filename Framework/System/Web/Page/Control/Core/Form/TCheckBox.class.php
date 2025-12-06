<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TCheckBox extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = 'input';
    const HTML_HAS_END_TAG = false;

    #[Prop, Stateful]
    public bool $checked = false;

    protected function onPostBack(?TPostBackEventArgs $args) : void {
        $this->checked = $args->value !== null;
    }
    
    protected function onRender(?TEventArgs $args) : void {
        $this->html->type = 'checkbox';
        $this->html->checked = $this->checked;
        $this->setHtmlName();

        parent::onRender($args);
    }   
}