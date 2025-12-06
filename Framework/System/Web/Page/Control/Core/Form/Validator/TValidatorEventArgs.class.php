<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\TControl;

class TValidatorEventArgs extends TEventArgs {
	public TControl $control;
}
