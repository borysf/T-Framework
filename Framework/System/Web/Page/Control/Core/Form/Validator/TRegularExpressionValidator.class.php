<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;

class TRegularExpressionValidator extends TValidator
{
	#[Prop]
	protected $regularExpression;
	
	protected function onRenderReady(?TEventArgs $e) : void {
		$regex = $this->regularExpression;
		$regex = str_replace('/', '\/', $regex);
		
		$this->clientValidateFunction = '/'.$regex.'/';
		
		parent::onRenderReady($e);
	}
	
	protected function onServerValidate(TValidatorEventArgs $e)
	{
		$this->_valid = preg_match('{'.$this->regularExpression.'}', $this->getControlToValidateObject()->text);
	}
}