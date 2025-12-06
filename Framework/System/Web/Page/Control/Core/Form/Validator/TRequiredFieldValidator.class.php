<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Core\Form\TCheckBox;
use System\Web\Page\Control\Core\Form\TInlineEditBox;
use System\Web\Page\Control\Core\Form\TTextBox;
use System\Web\Page\Control\Event\TEventArgs;

class TRequiredFieldValidator extends TValidator {
	protected function onCreate(?TEventArgs $e) : void {
		parent::onCreate($e);
		$this->clientValidateFunction = "if(control.type == 'checkbox' || control.type == 'radio') return control.checked; return control.value.trim() != '';";
	}

	protected function onServerValidate(TValidatorEventArgs $e) {
		$ctl = $this->getControlToValidateObject();
		
		if($ctl instanceOf TCheckBox)
		{
			$this->_valid = $ctl->checked;
			return;
		}

		if($ctl instanceOf TTextBox || $ctl instanceof TInlineEditBox) {
			$this->_valid = trim($ctl->text) != '';
			return;
		}
	}
}