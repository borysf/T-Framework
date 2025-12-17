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

    #[Prop]
    public bool $causesPostBack = false;

    protected function onPostBack(?TPostBackEventArgs $args) : void {        
        if ($args->value === null) {
            return;
        }

        switch (count($args->value)) {
            case 1:
                $this->checked = false;
                break;
            case 2:
                $this->checked = true;
                break;
        }
    }
    
    protected function onRender(?TEventArgs $args) : void {
        $this->html->type = 'checkbox';
        $this->html->checked = $this->checked;
        $this->setHtmlName();

        if ($this->causesPostBack) {
            $this->html->onclick .= ';__doPostBack()';
        }

        parent::onRender($args);
    }

    protected function onMount(?TEventArgs $args): void
    {
        parent::onMount($args);
        // hidden field of the same name is required to detect if the checkbox has been sent
        // with checked or unchecked state, otherwise restoring `checked` state upon postback
        // may not work properly in case when checkbox gets hidden during postbacks
        $this->parent->addControlNextTo(new THiddenField(['html.name' => $this->createHtmlName()]), $this);
    }
}