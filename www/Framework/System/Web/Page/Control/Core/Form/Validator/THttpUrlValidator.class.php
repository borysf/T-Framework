<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Event\TEventArgs;

class THttpUrlValidator extends TRegularExpressionValidator {
	protected function onCreate(?TEventArgs $e) : void {
		$this->regularExpression = "^((http(s)?://)?[\w.-]+(\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+)?$";
		parent::onCreate($e);
	}
}
