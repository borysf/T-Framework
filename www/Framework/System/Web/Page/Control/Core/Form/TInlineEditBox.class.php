<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TInlineEditBox extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;

    #[Prop, Stateful]
    public bool $editMode = false;

    #[Prop, Stateful]
    public int $maxLength = 0;

    public function customTagName() : array {
        return $this->editMode ? ['input', false] : ['span', true];
    }

    protected function onRender(?TEventArgs $args) : void {
        if ($this->editMode) {
            $this->html->type = 'text';
            $this->html->value = $this->text;
            if ($this->maxLength) {
                $this->html->maxlength = $this->maxLength;
            }
            $this->setHtmlName();
        }

        parent::onRender($args);
    }

    protected function renderContents(): void {
        if (!$this->editMode && $this->text) {
            echo htmlspecialchars($this->text);
        }
    }

    protected function onPostBack(?TPostBackEventArgs $args) : void  {
        if ($args->value === null) {
            return;
        }
        $this->text = $args->value[0];
    }
}
