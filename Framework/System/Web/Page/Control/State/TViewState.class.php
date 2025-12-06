<?php

namespace System\Web\Page\Control\State;

use System\Debug\TDebug;
use System\Http\Request\THttpRequest;
use System\Web\Page\Control\Core\Form\TFormControl;
use System\Web\Page\Control\Core\Form\THiddenField;
use System\Web\Page\Control\Core\Form\TForm;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\TTemplate;
use System\Web\Page\TPage;
use System\Web\TPostBackEventArgs;
use Throwable;

class TViewState
{
    private IViewStateProvider $__provider;
    private THttpRequest $__request;

    public function __construct(THttpRequest $request, IViewStateProvider $provider)
    {
        $this->__request = $request;
        $this->__provider = $provider;
    }

    public function restore(TPage $page)
    {
        if ($this->__request->method() == 'POST' && isset($this->__request->post->__VIEWSTATE)) {
            $page->__setIsPostBack(true);

            $viewState = $this->__read($this->__request->post->__VIEWSTATE, $this->__generateTemplatesHash($page));

            if (!$viewState) {
                return;
            }

            $pageEventArgs = null;

            if (isset($viewState[$page->getSystemId()])) {
                $vs = $viewState[$page->getSystemId()];
                $pageEventArgs = new TViewStateEventArgs($vs);
                $page->raise('onRestoreState', $pageEventArgs);
                $page->restoreState($vs);
            }

            $formControls = [];

            foreach ($page->findDescendantsBySystemIds(array_keys($viewState)) as $children) {
                $childSystemId = $children->getSystemId();
                $vs = $viewState[$childSystemId];

                $childEventArgs = new TViewStateEventArgs($vs);

                $children->raise('onRestoreState', $childEventArgs);
                $children->restoreState($vs);

                if ($children->usesTrait(TFormControl::class)) {
                    if ($children->getState()->visible) {
                        $postBackArgs = new TPostBackEventArgs(
                            isset($this->__request->post->__V[$childSystemId]) ? $this->__request->post->__V[$childSystemId] : null
                        );

                        $formControls[] = [$children, $postBackArgs];
                    }
                }

                $children->raise('onRestoreStateComplete', $childEventArgs);
            }

            if (isset($pageEventArgs)) {
                $page->raise('onRestoreStateComplete', $pageEventArgs);
            }

            foreach ($formControls as [$control, $args]) { //first loop on controls to let them apply new state based on submitted data
                $control->raise('onPostBack', $args);
            }

            foreach ($formControls as [$control, $args]) { //second loop to let them raise actions (i.e. TButton::onClick)
                $control->raise('onPostBackComplete', $args);
            }

            foreach ($page->iterateDescendants() as $control) {
                $control->raise('onRestore', null);
            }
        }
    }

    public function save(TPage $page)
    {
        if (!$page->stateful) {
            return;
        }

        $form = $page->findFirstDescendantByClass(TForm::class);

        if (!$form) {
            return;
        }

        $viewState = [];

        $viewState[$page->getSystemId()] = $page->getState()->dump();

        foreach ($page->iterateDescendantsPredicate(fn (TControl $control) => $control->stateful, true) as $children) {
            $id = $children->getSystemId();

            $viewState[$id] = $children->getState()->dump();
        }

        $state = new THiddenField(['html.name' => '__VIEWSTATE', 'text' => $this->__write($viewState, $this->__generateTemplatesHash($page))]);

        $form->addControl(new TTemplateLiteralWhite(['text' => "\n\t"]));
        $form->addControl($state);
        $form->addControl(new TTemplateLiteralWhite(['text' => "\n"]));
    }

    protected function __generateTemplatesHash(TPage $page)
    {
        $mtimes = [];
        foreach ($page->iterateDescendantsByClass(TTemplate::class) as $tpl) {
            $mtimes[$tpl::TEMPLATE_SOURCE_FILE] = $tpl::TEMPLATE_SOURCE_MODIFIED_AT;
        }
        ksort($mtimes);
        return sha1(implode(';', $mtimes));
    }

    protected function __write(array $state, string $fingerPrint): string
    {
        return $this->__provider->write([
            'u' => $this->__request->uri(),
            's' => $state,
            'h' => $fingerPrint
        ]);
    }

    protected function __read(string $state, string $fingerPrint): ?array
    {
        try {
            $data = $this->__provider->read($state);
        } catch (Throwable $e) {
            throw $e;
        }

        if (is_array($data) && isset($data['u']) && isset($data['s']) && $data['u'] == $this->__request->uri()) {
            if (!isset($data['h']) || $data['h'] != $fingerPrint) {
                throw new TViewStateException(
                    'Page template has been modified during view state transition.',
                    reason: 'View state relies on constant templates structure. If page is already rendered ' .
                        'and the template gets modified in the meantime (before posting it back), it ' .
                        'is possible that the resulting page would be rendered wrong. Please refresh ' .
                        'the page with GET method and retry.'
                );
            }

            return $data['s'];
        }

        return null;
    }
}
