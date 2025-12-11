<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Event\TEventArgs;

class TJSONValidator extends TValidator {
	protected function onCreate(?TEventArgs $e) : void {
		parent::onCreate($e);
		$this->clientValidateFunction = "try { var v = control.value.trim(); JSON.parse(v); } catch(e) { return false; } return true";
	}

	protected function onServerValidate(TValidatorEventArgs $e) {
		$ctl = $this->getControlToValidateObject();

		if (trim($ctl->text) == '') {
			return true;
		}

        return @json_decode($ctl->text) ? true : false;
	}
}
