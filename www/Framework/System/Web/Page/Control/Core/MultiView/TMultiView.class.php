<?php
namespace System\Web\Page\Control\Core\MultiView;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Core\MultiView\TView;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;

/**
 * Displays multiple views and switches between them. A view is an instance of `TView`
 * control.
 */
class TMultiView extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const CHILDREN_TYPES_ALLOW = [TView::class];
    const CHILDREN_TYPES_IGNORE = [TTemplateLiteralWhite::class];

    /** Current (active) view index to show. */
    #[Prop, Stateful]
    public int $activeViewIndex = 0;

    protected function onRender(?TEventArgs $args) : void {
        foreach($this->getControls() as $k => $view) {
            $view->visible = $k == $this->activeViewIndex;
        }
    }
}
