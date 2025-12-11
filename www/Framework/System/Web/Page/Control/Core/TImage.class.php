<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;

/**
 * Represents an image on a page.
 */
class TImage extends TControl {
    const HTML_TAG_NAME = 'img';
    const HTML_HAS_END_TAG = false;
    const CHILDREN_TYPES_ALLOW = false;

    /** 
     * Represents a HTML `src` attribute. It can be set to one of the following: 
     * well formed URL, URL annotation string (`[route_name [...param=value]]`), 
     * PHP array as accepted by `THttpRouter::generate()` method.
     */
    #[Prop, Stateful]
    public string|array|null $source = null;

    protected function onRender(?TEventArgs $args) : void {
        if (is_string($this->source)) {
            if (preg_match('{^\s*\[.*\]\s*$}', $this->source)) {
                $this->html->src = $this->app->router->generateFromString($this->source);
            } else {
                $this->html->src = $this->source;
            }
        } else if (is_array($this->source)) {
            $this->html->src = $this->app->router->generate(...$this->source);
        }
    }
}