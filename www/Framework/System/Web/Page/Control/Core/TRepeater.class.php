<?php
namespace System\Web\Page\Control\Core;

use System\DataSource\TDataSourceEventArgs;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\DataBound\TDataBoundControl;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\TControlPropTemplate;
use System\Web\Page\Control\Template\TTemplate;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;

/**
 * Repeats its content for given data source and binds the data
 * to every single item.
 */
class TRepeater extends TDataBoundControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const HTML_HAS_ATTRIBUTES = false;

    const CHILDREN_TYPES_ALLOW = [TTemplate::class, TContainer::class];
    const CHILDREN_TYPES_IGNORE = [TTemplateLiteralWhite::class];

    /** Template string for header. The header is rendered above all the items. */
    #[Prop]
    public TControlPropTemplate $headerTemplate;

    /** Template string for footer. The footer is rendered below all the items. */
    #[Prop]
    public TControlPropTemplate $footerTemplate;

    /** Template string for single item. */
    #[Prop]
    public TControlPropTemplate $itemTemplate;

    /** Template string for separators between items. */
    #[Prop]
    public TControlPropTemplate $separatorTemplate;

    /** Template string to render when bound data source is empty. */
    #[Prop]
    public TControlPropTemplate $emptyTemplate;

    /** Whether to render header when bound data source is empty. */
    #[Prop]
    public bool $showHeaderWhenEmpty = false;

    /** Whether to render footer when bound data source is empty. */
    #[Prop]
    public bool $showFooterWhenEmpty = false;

    private ?TTemplate $__headerItem;
    private ?TTemplate $__footerItem;
    private TContainer $__itemsContainer;
    private TContainer $__headerContainer;
    private TContainer $__footerContainer;

    protected function createItem(string|int $key, int $count, mixed $data, int|string $index, ?int $iteration = null): TControl {
        $_ = null;

        $args = ['args' => new TDataBindEventArgs(
            item: $_, 
            data: $data, 
            count: $count, 
            index: $index, 
            iteration: $iteration
        )];

        $template = $this->itemTemplate->instance($args);

        if (isset($this->separatorTemplate)) {
            return new TContainer(children: [
                $template,
                $iteration < $count - 1 ? $this->separatorTemplate->instance($args) : null
            ]);
        }

        return $template;
    }

    protected function getDataBindItem(TControl $item) : TControl {
        return $item instanceof TContainer ? $item->getControlAtIndex(0) : $item;
    }

    protected function createEmptyItem(): ?TControl {
        if (isset($this->__headerItem) && !$this->showHeaderWhenEmpty) {
            $this->__headerItem->remove();
            $this->__headerItem = null;
        }

        if (isset($this->__footerItem) && !$this->showFooterWhenEmpty) {
            $this->__footerItem->remove();
            $this->__footerItem = null;
        }

        if (isset($this->emptyTemplate)) {
            return $this->emptyTemplate->instance();
        }

        return null;
    }

    protected function onAddItem(?TDataSourceEventArgs $args) : void {
        if (isset($this->separatorTemplate) && $args->index > 0 && $args->index == count($this->itemsContainer()->getControls()) - 1) {
            $this->itemsContainer()->getControlAtIndex($args->index - 1)->addControl(
                $this->separatorTemplate->instance()
            );
        }
    }

    protected function onRemoveItem(?TDataSourceEventArgs $args) : void {
        if (isset($this->separatorTemplate) && $args->index > 0 && $args->index == count($this->itemsContainer()->getControls())) {
            $this->itemsContainer()->getControlAtIndex($args->index - 1)->removeControlAtIndex(1);
        }
    }

    protected function itemsContainer(): TControl {
        return $this->__itemsContainer;
    }

    protected function onMount(?TEventArgs $args): void
    {
        parent::onMount($args);
        
        $this->__headerContainer = new TContainer;
        $this->addControl($this->__headerContainer);

        $this->__itemsContainer = new TContainer;
        $this->addControl($this->__itemsContainer);

        $this->__footerContainer = new TContainer;
        $this->addControl($this->__footerContainer);
    }

    protected function onRender(?TEventArgs $args): void
    {
        $count = $this->dataBound ? count($this->dataSource) : 0;

        if (isset($this->headerTemplate)) {
            if (($count == 0 && $this->showHeaderWhenEmpty) || $count > 0) {
                $this->__headerItem = $this->headerTemplate->instance();
                $this->__headerContainer->addControl($this->__headerItem);
            }
        }

        if (isset($this->footerTemplate)) {
            if (($count == 0 && $this->showFooterWhenEmpty) || $count > 0) {
                $this->__footerItem = $this->footerTemplate->instance();
                $this->__footerContainer->addControl($this->__footerItem);
            }
        }

        parent::onRender($args);
    }

    protected function onDataBindBegin(TDataBindEventArgs $args) : void {}
    protected function onDataBindComplete(TDataBindEventArgs $args) : void {}

    protected function onHeaderDataBind(TDataBindEventArgs $args) : void {}
    protected function onFooterDataBind(TDataBindEventArgs $args) : void {}
}