<?php
namespace System\Web\Page\Control\Template;

use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\Loader\TTemplateLoader;
use System\Web\Page\Control\Template\TTemplate;
use System\Web\Page\TPage;

/**
 * Base class for all controls having its own template.
 */
abstract class TTemplatedControl extends TControl {
    final public const HTML_TAG_NAME = null;
    final public const HTML_HAS_END_TAG = false;
    final public const HTML_HAS_ATTRIBUTES = false;
    public const CHILDREN_TYPES_ALLOW = null;
    public const CHILDREN_TYPES_IGNORE = null;

    /** Template file extension. */
    protected const TEMPLATE_EXTENSION = '.tpl';

    private bool $__constructed = false;

    public function __construct(array $props = [], bool $autoLoadTemplate = true) {
        parent::__construct($props);

        if ($autoLoadTemplate) {
            $parent = $this::class;

            while (($parent = get_parent_class($parent)) && $parent != TTemplatedControl::class && $parent != TPage::class) {
                $template = TTemplateLoader::loadForClass($parent, $this);
                $this->__loadTemplate($template);
            }

            $template = TTemplateLoader::loadForClass($this::class, $this);
            $this->__loadTemplate($template);
        }

        $this->__constructed = true;
    }

    private function __loadTemplate(TTemplate $template): int {
        $this->__linkControlsFromTemplate($template);

        return $template::TEMPLATE_SOURCE_MODIFIED_AT;
    }

    private function __linkControlsFromTemplate(TTemplate $template) : void {
        foreach ($template->namedControls as $id => $control) {
            if (isset($this->$id)) {
                throw new TTemplatedControlException($this, 'Could not register control with ID: '.$id.': name already used');
            }

            $this->$id = $control;
        }
    }


    public function __set($name, $value) : void {
        if (!$this->__constructed && $value instanceof TControl) {
            $this->$name = $value;
        } else {
            parent::__set($name, $value);
        }
    }
}