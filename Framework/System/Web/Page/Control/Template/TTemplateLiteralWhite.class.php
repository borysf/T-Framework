<?php
namespace System\Web\Page\Control\Template;

use System\TApplication;
use System\Web\Page\Control\TControlException;

class TTemplateLiteralWhite extends TTemplateLiteral {
    public function __construct(array $props = [], array $children = []) {
        if (isset($props['text'])) {
            if (TApplication::isDevelopment() && !preg_match('{^\s*$}', $props['text'])) {
                throw new TControlException($this, 'only whitespace characters allowed');
            }
        }

        parent::__construct($props, $children);
    }
}