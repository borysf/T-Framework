<?php
namespace System\Web\Page\Control\Core;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;

class TContentPlaceHolder extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const CHILDREN_TYPES_ALLOW = [TContent::class];
    const CHILDREN_TYPES_IGNORE = [TTemplateLiteralWhite::class];
}