<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TTextBox extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;

    #[Prop]
    public string $textMode = 'text';

    protected function renderContents() : void  {
        if ($this->textMode == 'multiline') {
            echo htmlspecialchars($this->text ?: '');
        }
    }

    protected function customTagName() : ?array {
        if ($this->textMode == 'multiline') {
            return ['textarea', true];
        } else {
            return ['input', false];
        }
    }

    protected function onRender(?TEventArgs $args) : void {
        if ($this->textMode == 'text' || $this->textMode == 'password') {
            $this->html->type = $this->textMode;
            $this->html->value = $this->textMode == 'password' ? '' : $this->text;
        }
        $this->setHtmlName();

        parent::onRender($args);
    }

    protected function onPostBack(?TPostBackEventArgs $args) : void  {
        if ($args->value === null) {
            return;
        }
        $this->text = $args->value[0];
    }
}
