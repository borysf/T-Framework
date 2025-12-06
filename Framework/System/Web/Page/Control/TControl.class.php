<?php

namespace System\Web\Page\Control;

use Error;
use Generator;
use ReflectionClass;
use ReflectionProperty;
use System\TApplication;
use System\Component\TComponent;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\State\TControlState;
use System\Web\Page\Control\Template\TTemplatedControl;
use System\Web\Page\AssetBundler\TAssetBundler;
use System\Web\Page\TPage;

abstract class TControl extends TComponent
{
    /** By default only index.css and index.js files are automatically published. Here you can define more such assets. */
    protected const ASSETS = [];

    /** Defines HTML tag name of this control. */
    public const HTML_TAG_NAME = 'span';

    /** Defines if closing tag should be rendered. */
    public const HTML_HAS_END_TAG = true;

    /** Defines if control allows setting HTML attributes. If set to true and `HTML_TAG_NAME` is not null, then `html` prop is initialized. */
    public const HTML_HAS_ATTRIBUTES = true;

    /**
     * Defines a list of children types this control allows. Notice: this list has no effect if your app runs in production mode.
     *
     * `null` - allow any,
     * `[...TControl::class]` - allow all listed types AND `TTemplateLiteralWhite`,
     * `false` - do not allow any including `TTemplateLiteralWhite`
     */
    public const CHILDREN_TYPES_ALLOW = null;

    /**
     * Defines a list of children types this control ignores. Control of this type will be skipped when added as a child.
     *
     * `null` - no ignore,
     * `[...TControl::class]` - ignore all listed types
     */
    public const CHILDREN_TYPES_IGNORE = null;

    /** Defines id of the control that the user can access it by. Must be unique within a single `TTemplatedControl` instance. */
    #[Prop]
    public readonly ?string $id;

    /** Defines text rendered in the control. When set, children controls are not rendered. */
    #[Prop, Stateful]
    public ?string $text = null;

    /** Defines HTML properties of the control. Exists only if `HTML_HAS_ATTRIBUTES` is set to true and `HTML_TAG_NAME` is not null. */
    #[Prop, Stateful]
    public TControlPropHtml $html;

    /** Defines if the control is visible and should be rendered. */
    #[Prop, Stateful]
    public bool $visible = true;

    /** Defines the key of the control. Primarily used by `TRepeater` to uniquely identify controls within rendered templates. */
    #[Prop, Stateful]
    public ?string $key = null;

    /** Defines if the state of the control should be preserved between postbacks. */
    #[Prop]
    public bool $stateful = true;

    /** References page instance that this control belongs to. */
    protected readonly TPage $page;

    /** References current application */
    protected readonly TApplication $app;

    /** Contains control state. Exists only if `$stateful` is set to true. */
    protected readonly TControlState $state;

    /** References to parent control. */
    protected ?TControl $parent = null;


    private array $children = [];
    private static array $__props = [];
    private static array $__classPath = [];

    /**
     * Returns custom HTML tag name in form of array where first element is tag name, second is boolean indicating whether has end tag name.
     */
    protected function customTagName(): ?array
    {
        return null;
    }

    /**
     * Creates new instance. Accepts props array in form of `['prop name' => 'value']` and array of children controls.
     * Once properties and children controls are set, raises `onCreate` event.
     */
    public function __construct(array $props = [], array $children = [])
    {
        if (!isset($this->page)) $this->page = TPage::$instance;
        if (!isset($this->app)) $this->app = $this->page->app;

        if ($this->stateful) {
            $this->state = new TControlState($this, $this->__getProps(Stateful::class, true));
        }

        if (($this::HTML_HAS_ATTRIBUTES && $this::HTML_TAG_NAME) || $this->customTagName()) {
            $this->html = new TControlPropHtml;
        }

        $this->__assignProps($props);

        foreach ($children as $child) {
            if ($child === null) {
                continue;
            }

            $this->addControl($child);
        }

        if (!empty($this::ASSETS)) {
            TAssetBundler::lookupAssets(self::$__classPath[$this::class], $this::ASSETS);
        }

        $this->raise('onCreate');
    }

    private function __getProps(string $attributeClass, bool $initializedOnly): array
    {
        $result = [];
        $thisClass = $this::class;

        if (!isset(self::$__props[$thisClass])) {
            self::$__props[$thisClass] = [];
        }

        $classProps = &self::$__props[$thisClass];

        if (!isset($classProps[$attributeClass])) {
            $ref = new ReflectionClass($thisClass);
            $props = $classProps[$attributeClass] = $ref->getProperties();
            self::$__classPath[$thisClass] = $ref->getFileName();
        }

        $props = &$classProps[$attributeClass];

        foreach ($props as $prop) {
            $attributes = $prop->getAttributes($attributeClass);
            $isInitialized = $prop->isInitialized($this);

            if (!empty($attributes) && (($initializedOnly && $isInitialized) || !$initializedOnly)) {
                $result[$prop->getName()] = $isInitialized ? $prop->getValue($this) : null;
            }
        }

        return $result;
    }

    private function __assignProps(array $props): void
    {
        $knownProps = $this->__getProps(Prop::class, false);

        if ($this->stateful) {
            $mapToState = array_keys($this->__getProps(Stateful::class, true));
        }

        foreach ($props as $name => $value) {
            $path = explode('.', $name);

            if (!array_key_exists($path[0], $knownProps)) {
                throw new TControlException($this, 'not a prop: ' . $name); //@todo exception
            }

            $current = $this;

            for ($i = 0, $l = count($path) - 1; $i < $l; $i++) {
                $key = $path[$i];

                if (!property_exists($current, $key)) {
                    throw new TControlException($this, 'undefined prop: ' . $name); //@todo exception
                }

                $current = $current->$key;
            }

            $name = end($path);

            $current->$name = $value;
        }

        if ($this->stateful && isset($this->state)) {
            foreach ($mapToState as $prop) {
                try {
                    $this->state->$prop = &$this->$prop;
                } catch (Error $e) {
                    throw new TControlException($this, 'prop ' . $this::class . '::$' . $prop . ' must be initialized in order to be stateful');
                }
            }
        }
    }

    /**
     * Restores control state from given state array. This method is used internally by the framework and should not be used by the user.
     */
    public function restoreState(array $state): void
    {
        if (!isset($this->state)) {
            return;
        }

        $this->state->restore($state);
        $mapToState = $this->__getProps(Stateful::class, false);

        foreach ($mapToState as $name => $_) {
            if (!isset($this->$name) && array_key_exists($name, $state)) {
                $prop = new ReflectionProperty($this, $name);
                $prop = new ReflectionProperty($prop->getDeclaringClass()->getName(), $name);
                $prop->setValue($this, $state[$name]);
            }
        }
    }

    /**
     * Maps control properties to TControlState and returns it.
     */
    public function getState(): ?TControlState
    {
        if ($this->stateful && isset($this->state)) {
            $mapToState = $this->__getProps(Stateful::class, true);
            foreach ($mapToState as $k => $v) {
                $this->state->$k = $v;
            }

            return $this->state;
        }

        return null;
    }

    /**
     * Returns first parent of TTemplatedControl type this control belongs to.
     */
    public function getParentTemplatedControl(): ?TTemplatedControl
    {
        if (isset($this->parent)) {
            if (!($this->parent instanceof TTemplatedControl)) {
                return $this->parent->getParentTemplatedControl();
            }

            return $this->parent;
        }

        return null;
    }

    /**
     * Returns system id of the control. Returns null if control has no parent.
     */
    public function getSystemId(): ?string
    {
        if ($this->parent) {
            $key = $this->key !== null ? ':' . $this->key : '';
            $parentId = $this->parent->getSystemId();

            if ($parentId === null) {
                return null;
            }

            return $parentId . '_' . $this->parent->__controlIndex($this) . $key;
        }

        return null;
    }

    /**
     * Returns list of all children controls.
     */
    final public function getControls(): ?array
    {
        return isset($this->children) ? $this->children : null;
    }

    /**
     * Removes control from parent and appends to the new parent if provided.
     */
    final public function setParent(TControl $parent = null): void
    {
        if ($parent !== $this->parent) {
            if ($this->parent) {
                $this->parent->removeControl($this);
            }

            $this->parent = $parent;

            if ($parent) {
                $parent->addControl($this);
            }
        }
    }

    /**
     * Removes given control.
     */
    final public function removeControl(TControl $control): void
    {
        if (isset($this->children)) foreach ($this->children as $k => $v) {
            if ($v === $control) {
                $this->removeControlAtIndex($k);
                break;
            }
        }
    }

    /**
     * Removes all children controls.
     */
    final public function removeAllControls(): void
    {
        if (isset($this->children)) {
            while ($count = count($this->children)) {
                $this->removeControlAtIndex($count - 1);
            }
        }
    }

    /**
     * Removes itself from parent.
     */
    final public function remove(): void
    {
        $this->parent->removeControl($this);
    }

    /**
     * Removes children control at given index and returns it. Returns null if there is no such control.
     */
    public function removeControlAtIndex(int $index): ?TControl
    {
        if (isset($this->children)) {
            $control = $this->children[$index];
            $control->parent = null;

            array_splice($this->children, $index, 1);

            return $control;
        }

        return null;
    }

    /**
     * Returns children control by given index. Returns null if there is no such control.
     */
    final public function getControlAtIndex(int $index): ?TControl
    {
        if (isset($this->children)) {
            return $this->children[$index];
        }

        return null;
    }

    /**
     * Adds children control at given index.
     * If index is null, the children is pushed to the last position.
     */
    public function addControl(TControl $control, int $index = null): void
    {
        if (TApplication::isDevelopment()) {
            if ($this::CHILDREN_TYPES_ALLOW === false) {
                throw new TControlException($this, 'control of type: ' . $control::class . ' not allowed as direct child', reason: 'This control does not allow any child controls');
            }

            if (is_array($this::CHILDREN_TYPES_ALLOW)) {
                $allowed = $control instanceof TTemplateLiteralWhite;

                if (!$allowed && !empty($this::CHILDREN_TYPES_ALLOW)) {
                    foreach ((array) $this::CHILDREN_TYPES_ALLOW as $type) {
                        if ($control instanceof $type) {
                            $allowed = true;
                            break;
                        }
                    }
                }

                if (!$allowed) {
                    throw new TControlException($this, 'control of type: ' . $control::class . ' not allowed as direct child', reason: 'allowed types: [ ' . implode(', ', $this::CHILDREN_TYPES_ALLOW) . ' ]');
                }
            }
        }

        if (is_array($this::CHILDREN_TYPES_IGNORE) && in_array($control::class, (array) $this::CHILDREN_TYPES_IGNORE)) {
            return;
        }

        if ($control->parent !== $this) {
            if ($control->parent) {
                $control->parent->removeControl($control);
            }

            $control->parent = $this;
        }

        if (!isset($this->children)) {
            $this->children = [];
        }

        if (!in_array($control, $this->children, true)) {
            if ($index === null) {
                $this->children[] = $control;
            } else if (!isset($this->children[$index])) {
                $this->children[$index] = $control;
            } else {
                array_splice($this->children, $index, 0, array($control));
            }

            ksort($this->children);
        }

        if ($this->__isPageAtTopLevel()) $control->raise('onMount');
    }

    private function __isPageAtTopLevel(): bool
    {
        if (!isset($this->parent)) {
            return false;
        }

        if ($this->parent instanceof TPage) {
            return true;
        }

        return $this->parent->__isPageAtTopLevel();
    }

    /**
     * Iterates all descendants of this control.
     */
    final public function iterateDescendants(): Generator
    {
        if (isset($this->children)) foreach ($this->children as $child) {
            yield $child;

            foreach ($child->iterateDescendants() as $_child) {
                yield $_child;
            }
        }
    }

    /**
     * Iterates all descendants of the control using callable predicate as a matcher.
     * Predicate gets `TControl` as argument. If predicate returns `true`, the descendant is yielded.
     * Otherwise continues iteration deeper unless `$skipDescendantsIfPredicateFails` is `true`.
     */
    final public function iterateDescendantsPredicate(callable $predicate, bool $skipDescendantsIfPredicateFails = false): Generator
    {
        if (isset($this->children)) foreach ($this->children as $child) {
            if ($predicate($child)) {
                yield $child;
            } else if ($skipDescendantsIfPredicateFails) {
                continue;
            }

            foreach ($child->iterateDescendantsPredicate($predicate) as $_child) {
                yield $_child;
            }
        }
    }

    /**
     * Finds first descendant by supplied class name. Returns `null` on failure.
     */
    final public function findFirstDescendantByClass(string $className): ?TControl
    {
        foreach ($this->iterateDescendants() as $child) {
            if ($child instanceof $className) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Iterates throuth all descendants of this control and yields only those
     * who are instance of provided `$className`.
     */
    final public function iterateDescendantsByClass(string $className): Generator
    {
        foreach ($this->iterateDescendants() as $child) {
            if ($child instanceof $className) {
                yield $child;
            }
        }

        return null;
    }

    /**
     * Iterates through all descendants of this control and yields only those
     * whose `systemId` is in provided search array.
     */
    final public function findDescendantsBySystemIds(array $systemIds): Generator
    {
        foreach ($this->iterateDescendants() as $children) {
            if (empty($systemIds)) {
                return;
            }

            $pos = array_search($children->getSystemId(), $systemIds);

            if ($pos !== false) {
                array_splice($systemIds, $pos, 1);
                yield $children;
            }
        }
    }

    /**
     * Shows control. Alias for setting `visible` to true.
     */
    final public function show(): void
    {
        $this->visible = true;
    }

    /**
     * Hides control. Alias for setting `visible` to false.
     */
    final public function hide(): void
    {
        $this->visible = false;
    }

    /**
     * Renders control if is set to visible. After control is rendered all
     * its properties are released to free up the memory.
     */
    protected function render(): void
    {
        if (!$this->visible) {
            return;
        }

        $this->raise('onRender');

        $tagName = $this::HTML_TAG_NAME;
        $hasEndTag = $this::HTML_HAS_END_TAG;

        if (!$tagName && ($custom = $this->customTagName())) {
            [$tagName, $hasEndTag] = $custom;
        }

        if ($tagName) {
            echo '<' . $tagName . (isset($this->html) ? $this->html->__toString() : '');

            if ($hasEndTag || $this::HTML_HAS_END_TAG === true || ($this::HTML_HAS_END_TAG === null && (!!(string)$this->text || (isset($this->children) && !empty($this->children))))) {
                echo '>';

                $this->renderContents();

                echo '</' . $tagName . '>';
            } else {
                echo '/>';
            }
        } else {
            $this->renderContents();
        }

        foreach ($this as $k => $v) {
            try {
                unset($this->$k);
            } catch (Error $e) {
            }
        }
    }

    /**
     * Renders control content. If `text` prop is set, it renders the value
     * as HTML content. Otherwise renders children controls.
     */
    protected function renderContents(): void
    {
        if ($this->text !== null) {
            echo htmlspecialchars($this->text);
        } else if (isset($this->children)) {
            foreach ($this->children as $child) {
                $child->render();
            }
        }
    }


    private function __controlIndex(TControl $control): ?int
    {
        return isset($this->children) ? array_search($control, $this->children, true) : false;
    }

    /**
     * Propagates given event down the controls hierarchy.
     */
    final protected function propagate(string $eventName, ?TEventArgs $args = null, ?callable $predicate = null): void
    {
        if ($this->eventExists($eventName)) {
            if ($predicate === null || $predicate($this)) {
                $this->raise($eventName);
                if (isset($this->children)) foreach ($this->children as $child) {
                    $child->propagate($eventName, $args, $predicate);
                }
            }
        }
    }

    /**
     * Checks if this control uses given trait.
     */
    final public function usesTrait(string $trait): bool
    {
        $parent = $this::class;

        do {
            $traits = class_uses($parent);
            if (in_array($trait, $traits)) {
                return true;
            }
        } while ($parent = get_parent_class($parent));

        return false;
    }

    /**
     * Fired when TControl instance is added to the parent control.
     */
    protected function onMount(?TEventArgs $args): void
    {
    }

    /**
     * Fired when TControl instance is created. At this point all props and children controls have been assigned.
     */
    protected function onCreate(?TEventArgs $args): void
    {
    }

    /**
     * Fired when TControl instance is about to restore its state upon postback.
     */
    protected function onRestoreState(?TEventArgs $args): void
    {
    }

    /**
     * Fired when TControl instance has restored its state upon postback.
     */
    protected function onRestoreStateComplete(?TEventArgs $args): void
    {
    }

    /**
     * Fired when TControl instance has fully restored.
     */
    protected function onRestore(?TEventArgs $args): void
    {
    }

    /**
     * Fired when page is about to render.
     */
    protected function onRenderReady(?TEventArgs $args): void
    {
    }

    /**
     * Fired when TControl instance is being rendered. At this point you have the last opportunity to modify control's properties.
     */
    protected function onRender(?TEventArgs $args): void
    {
    }
}
