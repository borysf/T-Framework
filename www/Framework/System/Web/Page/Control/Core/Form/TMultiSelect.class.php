<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Core\Form\List\TBaseListControl;
use System\Web\Page\Control\Core\Form\List\TListOption;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\TPostBackEventArgs;

class TMultiSelect extends TBaseListControl {
    #[Prop, Stateful]
    public array $selectedValues = [];

    #[Prop]
    public bool $causesPostBack = false;

    protected function shouldSelectOption(TListOption $option) : bool {
        return in_array($option->value, $this->selectedValues);
    }

    protected function onPostBack(?TPostBackEventArgs $args) : void {
        if ($args->value === null) {
            return;
        }
        $this->selectedValues = $args->value;
    }

    protected function onRender(?TEventArgs $args) : void {
        $this->html->multiple = true;

        if ($this->causesPostBack) {
            $this->html->onchange .= ';__doPostBack()';
        }

        parent::onRender($args);
    }
}