<?php
namespace System\Web\Page\Control\Core;

use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;

/**
 * Represents a link on a page.
 */
class THyperLink extends TControl {
    const HTML_TAG_NAME = 'a';
    const HTML_HAS_END_TAG = true;

    /**
     * Represents a HTML `href` attribute. It can be set to one of the following:
     * well formed URL, URL annotation string (`[route_name [...param=value]]`),
     * PHP array as accepted by `THttpRouter::generate()` method.
     */
    #[Prop, Stateful]
    public string|array|null $navigateUrl = null;

    /**
     * Defines CSS class to apply to the link when its path matches requested
     * URL path.
     */
    #[Prop]
    public ?string $activeCssClass = null;

    /**
     * Defines regular expression to use to match the active URL against.
     * Use `{path}` as a placeholder for link's URL.
     */
    #[Prop]
    public string $activeCssClassRegex = '^{path}((/.*)|$)';

    protected function onRender(?TEventArgs $args) : void {
        if (is_string($this->navigateUrl)) {
            if (preg_match('{^\s*\[.*\]\s*$}', $this->navigateUrl)) {
                $this->html->href = $this->app->router->generateFromString($this->navigateUrl);
            } else {
                $this->html->href = $this->navigateUrl;
            }
        } else if (is_array($this->navigateUrl)) {
            $this->html->href = $this->app->router->generate(...$this->navigateUrl);
        }

        if ($this->activeCssClass) {
            $regexp = str_replace('{path}', preg_quote(parse_url($this->html->href, PHP_URL_PATH)), $this->activeCssClassRegex);

            if (preg_match('{'.$regexp.'}', $this->app->request->uri())) {
                $this->html->class->add($this->activeCssClass);
            } else {
                $this->html->class->remove($this->activeCssClass);
            }
        }
    }
}
