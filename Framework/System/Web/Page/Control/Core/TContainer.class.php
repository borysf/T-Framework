<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\TControl;

/**
 * Basic container. This control does not render its HTML tag,
 * however can be used to store other controls.
 */
class TContainer extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const HTML_HAS_ATTRIBUTES = false;
}