<?php
namespace Docs\classes;

use System\Web\Page\Control\Core\TPanel;
use System\Web\Page\Control\Core\THyperLink;
use System\Web\Page\Control\Core\TLiteral;
use System\Web\Page\Control\Core\TText;
use System\Web\Page\Control\Event\TEventArgs;

class TComment extends TPanel {
    public DocComment $comment;

    protected function render() : void {
        if (!isset($this->comment) || !$this->comment->text) {
            return;
        }
        parent::render();
    }

    protected function onRender(?TEventArgs $args) : void {
        $this->html->class->add('comment');
        if ($this->comment->inheritedFrom) {
            $this->html->class->add('inherited');
        }
        if ($this->comment->inheritedFrom) {
            $this->addControl(new TText([
                    'html.class' => 'badge inherited',
                    'html.title' => 'Description inherited from base class'
                ], [
                    new THyperLink([
                        'text' => $this->__toShortName($this->comment->inheritedFrom), 
                        'navigateUrl' => ['system:docs:path', ['path' => str_replace('\\', '.', $this->comment->inheritedFrom)]]
                    ]
                )
            ]));
        }

        $this->addControl(new TLiteral(['encode' => false, 'text' => preg_replace_callback('{(`)(?:(?!\1)(?:[^`]))*\1}', function ($match) {
            return '<span class="code">'.trim($match[0], '`').'</span>';
        }, preg_replace("{\n\s*\n}", '<br>', htmlspecialchars($this->comment->text)))]));
    }

    private function __toShortName(string $name) : string {
        $pos = strrpos($name, '\\');

        $first = substr($name, 0, 1);

        if ($pos === false) {
            return $name;
        }

        return ($first == '?' ? '?' : '').substr($name, $pos + 1);
    }
}