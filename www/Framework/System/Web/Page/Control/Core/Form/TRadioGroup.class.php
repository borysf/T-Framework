<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TRadioGroup extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const HTML_HAS_ATTRIBUTES = false;

    #[Prop, Stateful]
    public int|string|null $value = null;

    protected function renderContents() : void {
        // call original TControl's implementation instead of TFormControl's
        parent::renderContents();
    }

    protected function onPostBackComplete(?TPostBackEventArgs $args) : void {
        if ($args->value !== null) {
            $this->value = $args->value[0];
        }
    }

    protected function onRender(?TEventArgs $args) : void {
        foreach ($this->iterateDescendantsByClass(TRadio::class) as $radio) {
            $radio->html->name = $this->createHtmlName();
            $radio->checked = $radio->value == $this->value;
        }
    }
}
