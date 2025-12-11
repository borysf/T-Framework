<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\TControlException;

class TContent extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;

    #[Prop]
    public string $contentPlaceHolderId;

    protected function onMount(?TEventArgs $args) : void {
        $parent = $this->getParentTemplatedControl();
        $parent = $parent ?: $this->page;

        $placeHolder = $parent->{$this->contentPlaceHolderId};

        if (!($placeHolder instanceof TContentPlaceHolder)) {
            throw new TControlException($this, 'contentPlaceHolderId prop must point to control of type TContentPlaceHolder');
        }
        
        $this->setParent($placeHolder);

        parent::onMount($args);
    }
}