<?php
namespace System\Web\Page\Control\Core\Form\Validator;

use System\Web\Page\Control\Core\Form\TButton;
use System\Web\Page\Control\Core\Form\TFormControl;
use System\Web\Page\Control\Core\Form\TForm;
use System\Web\Page\Control\Core\THtmlElement;
use System\Web\Page\Control\Core\TLiteral;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;

abstract class TValidator extends TControl {
	const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;

    const ASSETS = ['TValidator.js' => 'validator.js'];

	#[Prop]
	public string $display = 'dynamic';

	#[Prop]
	public bool $enableClientScript = true;

	#[Prop]
	public string $controlToValidate = '';

	#[Prop]
	public string $controlCssClass = '';

	#[Prop]
	public string $clientValidateFunction = '';

	#[Prop]
	public string $errorMessage = '';

	#[Prop]
	public string $validationGroup = '';

	#[Prop]
	public bool $focusOnError = true;

	#[Prop]
	public bool $displayMessage = true;



	protected bool $_valid = true;
	protected bool $_rendered = false;
	protected TFormControl $_ctlToValidate;

	protected static $_validators = array();

	private static ?TControl $_scriptControl = null;

	public static function getInstances() {
		return self::$_validators;
	}

	public function getControlToValidateObject() : TControl {
		return $this->page->{$this->controlToValidate};
	}

	public function renderContents() : void {
		if ($this->display == 'static' || !$this->_valid)
		{
			echo $this->errorMessage;
		}
	}

	public function validate() {
		$e = new TValidatorEventArgs;
		$e->control = $this->getControlToValidateObject();

		$this->raise('onServerValidate', $e);

		if(!$this->_valid)
		{
			$e->control->html->class->add($this->controlCssClass);
		}
		else
		{
			$e->control->html->class->remove($this->controlCssClass);
		}

		return $this->_valid;
	}

	protected function onCreate(?TEventArgs $args) : void {
		self::$_validators[] = $this;
	}

	protected function customTagName() : array {
		return [$this->display == 'static' ? 'div' : 'span', true];
	}

	protected function onRenderReady(?TEventArgs $e) : void {
		$this->html->id = $this->html->id ?: $this->getSystemId();

		if ($this->enableClientScript && ($form = $this->page->findFirstDescendantByClass(TForm::class))) {
			$controlToValidate 			= $this->getControlToValidateObject($this->controlToValidate);
			$controlToValidateId		= $controlToValidate->html->id ?: $controlToValidate->getSystemId();

			$setFocus 					= $this->focusOnError;
			$displayMessage 			= (int) $this->displayMessage;
			$clientValidateFunction		= preg_match('{^/.*/$}', $this->clientValidateFunction) ? $this->clientValidateFunction : 'function(control) { '.$this->clientValidateFunction.' }';
			$errorMessage				= str_replace("'", "\\'", $this->errorMessage);
			$controlCssClass			= $this->controlCssClass;
			$validationGroup			= $this->validationGroup;

			$controlToValidate->html->id = $controlToValidateId;

			if (!self::$_scriptControl) {
				$form->addControl(self::$_scriptControl = new THtmlElement([
					'name' => 'script',
					'hasEndTag' => true
                ]));

				foreach ($form->iterateDescendantsByClass(TButton::class) as $button) {
					if ($button->causesValidation && $button->visible) {
						$button->html->id = $button->html->id ? $button->html->id : $button->getSystemId();

						self::$_scriptControl->addControl(new TLiteral(['encode' => false, 'text' => "TValidator.causeValidation('click', '{$button->html->id}', '{$button->validationGroup}');\n"]));
					}
				}
			}

			self::$_scriptControl->addControl(new TLiteral([
				'encode' => false,
				'text' => "TValidator.add('{$controlToValidateId}','{$this->html->id}',{$clientValidateFunction},'{$errorMessage}','{$controlCssClass}',{$setFocus},{$displayMessage},'{$validationGroup}','{$this->display}');\n"
			]));
		}

		if ($this->display == 'static') {
			if ($this->_valid) {
				$this->html->style->visibility = 'hidden';
			} else {
				$this->html->style->visibility = 'visible';
			}
		}
	}

	protected function onServerValidate(TValidatorEventArgs $e) {}
}
