<?php
namespace System\Web\Page\Control\Template;

use System\Web\Page\TPage;
use System\Web\Page\Control\TControl;

class TTemplate extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;
    const HTML_HAS_ATTRIBUTES = false;

    const TEMPLATE_SOURCE_FILE        = '<none>';
    const TEMPLATE_SOURCE_MODIFIED_AT = 0;
    const TEMPLATE_SOURCE_COMPILED_AT = 0;

    public readonly TPage $page;
    public array $namedControls = [];

    protected readonly TControl $ownerControl;
    protected readonly array $__data;

    public function __construct(TControl $ownerControl, array $data = []) {
        $this->ownerControl = $ownerControl;
        $this->__data = $data;
        parent::__construct();
    }

    /**
     * Returns true if template is fresh, meaning that source file has not been modified
     * since the template has been compiled. Otherwise returns false.
     */
    public function isFresh() : bool {
        return $this::TEMPLATE_SOURCE_FILE == self::TEMPLATE_SOURCE_FILE || (is_file($this::TEMPLATE_SOURCE_FILE) && filemtime($this::TEMPLATE_SOURCE_FILE) === self::TEMPLATE_SOURCE_MODIFIED_AT);
    }
}