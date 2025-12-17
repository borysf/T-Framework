<?php
namespace System\Web\Page\Control\Core\Form;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\TControl;

/**
 * Renders a HTML form. This class has special use by the framework.
 * `T` is using this control to support View State, create CSRF-protected
 * fields and perform form fields validation. Only one instance should be 
 * created on a single page.
 */
class TForm extends TControl {
    const HTML_TAG_NAME = 'form';
    const HTML_HAS_END_TAG = true;

    protected const ASSETS = ['TForm.js'];

    protected function onRender(?TEventArgs $args) : void {
        $this->html->method = 'post';
    }
}