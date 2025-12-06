<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\TControl;

/** Displays a panel (`<div>`) that can be used as a container for children controls. */
class TPanel extends TControl {
    const HTML_TAG_NAME = 'div';
    const HTML_HAS_END_TAG = true;
}