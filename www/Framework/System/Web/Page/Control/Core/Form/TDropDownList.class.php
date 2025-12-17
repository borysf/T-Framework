<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Core\Form\List\TBaseListControl;
use System\Web\Page\Control\Core\Form\List\TListOption;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\TPostBackEventArgs;

class TDropDownList extends TBaseListControl {
    #[Prop, Stateful]
    public mixed $selectedValue = null;

    #[Prop]
    public bool $causesPostBack = false;

    protected function shouldSelectOption(TListOption $option) : bool {
        return $option->value == $this->selectedValue;
    }

    protected function onPostBack(?TPostBackEventArgs $args) : void {
        if ($args->value === null) {
            return;
        }
        $this->selectedValue = $args->value[0];
    }

    protected function onRender(?TEventArgs $args): void {
        if ($this->causesPostBack) {
            $this->html->onchange .= ';__doPostBack()';
        }

        parent::onRender($args);
    }
}