<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Core\Form\TInlineEditBox;
use System\Web\Page\Control\Core\Form\TTextBox;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;

class TLengthValidator extends TValidator {
	#[Prop, Stateful]
	public int $max = 0;

	#[Prop, Stateful]
	public int $min = 0;

	protected function onCreate(?TEventArgs $e) : void {
		parent::onCreate($e);
		$this->clientValidateFunction = "if(control.type == 'text' || control.type == 'password' || control.tagName == 'textarea') return control.value.trim().length >= {$this->min}".($this->max > 0 ? ' && control.value.trim().length <= '.$this->max.';' : ';');
	}

	protected function onServerValidate(TValidatorEventArgs $e) {
		$ctl = $this->getControlToValidateObject();
		
		if($ctl instanceOf TTextBox || $ctl instanceof TInlineEditBox) {
			$len = strlen(trim($ctl->text));

			$this->_valid = $len >= $this->min && ($this->max == 0 || $len <= $this->max);
		}
	}
}