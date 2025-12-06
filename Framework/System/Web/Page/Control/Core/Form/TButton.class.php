<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Core\Form\Validator\TValidator;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;
use System\Web\TPostBackEventArgs;

class TButton extends TControl {
    use TFormControl;

    const HTML_TAG_NAME = 'input';
    const HTML_HAS_END_TAG = false;

    #[Prop, Stateful]
    public mixed $data = null;

    #[Prop, Stateful]
    public bool $causesValidation = true;

    #[Prop, Stateful]
    public bool $causesPostBack = true;

    #[Prop, Stateful]
    public ?string $validationGroup = null;

    protected function onRender(?TEventArgs $args) : void {
        $this->html->type = $this->causesPostBack ? 'submit' : 'button';
        $this->html->value = $this->text;
        $this->setHtmlName();
        
        parent::onRender($args);
    }

    // protected function onPostBack(TPostBackEventArgs $args) {
    //     if ($args->value === null) {
    //         return;
    //     }
        
    //     $this->_clicked = true;
    // }

    protected function onPostBackComplete(?TPostBackEventArgs $args) : void {
        if (!$args->value) {
            return;
        }

        $args = new TButtonEventArgs;
        $args->data = $this->data;

        $valid = true;
        
        if ($this->causesValidation) {
            foreach(TValidator::getInstances() as $ctl) {
                if($ctl->validationGroup == $this->validationGroup) {
                    if(!$ctl->validate()) {
                        $valid = false;
                    }
                }
            }
        }

        if ($valid) {
            $this->raise('onClick', $args);
        }
    }

    protected function onClick(?TButtonEventArgs $e) : void {}
}