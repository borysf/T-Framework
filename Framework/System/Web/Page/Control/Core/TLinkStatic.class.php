<?php
namespace System\Web\Page\Control\Core;

use Exception;
use System\TException;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;

class TLinkStatic extends TControl {
    const HTML_TAG_NAME = null;
    const HTML_HAS_END_TAG = false;

    #[Prop]
    protected ?string $url = null;

    #[Prop]
    protected bool $defer = false;

    protected function customTagName() : array {
        if (!$this->url) {
            return ['link', true];
        }

        $i = pathinfo($this->url);
        $ext = strtolower($i['extension']);

        switch ($ext) {
            case 'css':
                return ['link', false];
                
            case 'js':
                return ['script', true];

            default:
                throw new TException('Unknown file type: '.$ext);
        }
    }

    public function onRender(?TEventArgs $args) : void {
        $i = pathinfo($this->url);
        $ext = strtolower($i['extension']);

        if ($ext == 'css') {
            $this->html->type = 'text/css';
            $this->html->rel = 'stylesheet';
            $this->html->href = $this->url;
        } else if ($ext == 'js') {
            $this->html->type = 'text/javascript';
            $this->html->src = $this->url;
            $this->html->defer = $this->defer;
        }
    }
}
