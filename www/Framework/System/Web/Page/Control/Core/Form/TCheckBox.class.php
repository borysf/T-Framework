<?php
namespace System\Web\Page\Control\Core\Form;

use System\DataSource\ISelectionSource;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TCheckBox extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = 'input';
    const HTML_HAS_END_TAG = false;

    private THiddenField $_hidden;

    #[Prop, Stateful]
    public bool $checked = false;

    #[Prop, Stateful]
    public bool $causesPostBack = false;

    public ?ISelectionSource $selectionSource = null;

    protected function onCreate(?TEventArgs $args): void
    {
        parent::onCreate($args);
        $this->_hidden = new THiddenField;
    }

    protected function onMount(?TEventArgs $args): void
    {
        parent::onMount($args);
        // hidden field of the same name is required to detect if the checkbox has been sent
        // with checked or unchecked state, otherwise restoring `checked` state upon postback
        // may not work properly in case when checkbox gets hidden during postbacks
        $this->parent->addControlNextTo($this->_hidden, $this);
    }

    protected function onPostBack(?TPostBackEventArgs $args) : void {        
        if ($args->value === null) {
            return;
        }

        switch (count($args->value)) {
            case 1:
                $this->checked = false;
                if ($this->selectionSource !== null) {
                    $this->selectionSource->deselect($this->key);
                }
                break;
            case 2:
                $this->checked = true;
                if ($this->selectionSource !== null) {
                    $this->selectionSource->select($this->key);
                }
                break;
        }
    }
    
    protected function onRender(?TEventArgs $args) : void {
        $this->setHtmlName();

        $this->html->type = 'checkbox';
        $this->html->checked = $this->checked;
        $this->_hidden->html->name = $this->html->name;

        if ($this->causesPostBack) {
            $this->html->onclick .= ';T.doPostBack()';
        }

        parent::onRender($args);
    }
}