<?php

namespace System\Web\Page;

use ReflectionClass;
use System\Debug\TDebug;
use System\Http\Error\THttpError;
use System\Http\THttpCode;
use System\Web\Page\Control\Template\TTemplatedControl;
use System\Web\Action\TActionArgs;
use System\Web\Action\Action;
use System\TApplication;
use System\Web\Page\AssetBundler\TAssetBundler;
use System\Web\Page\AssetBundler\TAssetException;
use System\Web\Page\Control\Core\Form\THiddenField;
use System\Web\Page\Control\Core\Form\TForm;
use System\Web\Page\Control\Core\THead;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;
use System\Web\Scss\TScssCompiler;

/**
 * Base class for all pages.
 *
 * In `T` you create pages by composing it from templates where you define controls.
 * Controls are instances of TControl class.
 *
 * Pages are inheritable. This means that you can have a hierarchy of pages where root
 * page displays common UI and children pages display desired website fragments.
 *
 * When a page comes to life it first loads its template, creates and links controls
 * to its properties and, finally, fires Page_Init, then Page_Load methods. These are
 * entry points for your page logic.
 */
abstract class TPage extends TTemplatedControl
{
    protected const TEMPLATE_EXTENSION = '.page';

    /** Globally available current page instance. */
    public static TPage $instance;

    private static int $__instanceId = 0;

    /** References current application. */
    public readonly TApplication $app;

    /** References THead control (if any) */
    public readonly ?THead $head;

    private readonly string $__systemId;

    private array $__scripts = [];
    private array $__styles = [];
    private bool $__isPostBack = false;

    final public function __construct(TActionArgs $args, TApplication $application)
    {
        self::$instance = $this;

        $this->app = $application;

        $this->__systemId = 'ctl' . self::$__instanceId++;

        $this->Page_Init($args);

        parent::__construct();

        $this->propagate('onMount');

        $this->app->state->restore($this);

        $this->head = $this->findFirstDescendantByClass(THead::class);

        TDebug::memory($this::class, 'Page_Init');

        TDebug::log($this::class, 'initialized page instance with args', $args);
    }

    final public function getSystemId(): ?string
    {
        return $this->__systemId;
    }

    /**
     * INTERNAL USE ONLY. After page is initialized (Page_Init), this method calls
     * Page_Load() and then runs requested action, gathers and saves viewstate
     * information in TForm (if exists) and, finally, renders the page.
     */
    final public function load(TActionArgs $args): void
    {
        $this->Page_Load($args);

        TDebug::memory($this::class, 'Page_Load');

        TDebug::log($this::class, 'Page_Load completed with args:', $args);
    }

    final public function finish(): void
    {
        $this->app->state->save($this);
        $this->__createHead($this->findFirstDescendantByClass(THead::class));

        if ($token = $this->app->guard->getCsrfToken()) {
            $form = $this->findFirstDescendantByClass(TForm::class);

            if ($form) {
                $form->addControl(new THiddenField(['html.name' => '__CSRF', 'text' => $token]));
                $form->addControl(new TTemplateLiteralWhite(['text' => "\n"]));
            }
        }

        TDebug::log($this::class, 'onRenderReady');
        $this->propagate('onRenderReady', null, fn (TControl $control) => $control->visible);

        TDebug::memory($this::class, 'memory usage before render');
        $this->render();
        TDebug::log($this::class, 'render complete');
    }

    public function linkScript(string $url, array $attributes = [])
    {
        $this->__scripts[] = [$url, $attributes];
    }

    public function linkCss(string $url, bool $defer = false)
    {
        $this->__styles[] = $url;
    }

    public function __setIsPostBack(bool $isPostBack): void
    {
        $this->__isPostBack = $isPostBack;
    }

    public function isPostBack()
    {
        return $this->__isPostBack;
    }

    private function __createHead(?THead $head)
    {
        if ($head) {
            $assets = TAssetBundler::buildForPage($this);

            foreach ($assets as $asset) {
                $url = $this->app->router->generate('system:assets:bundle', [
                    'bundleId' => $asset->bundleId,
                    'file' => $asset->fileName
                ]);

                switch ($asset->type) {
                    case 'js':
                        $head->linkScript($url);
                        break;
                    case 'css':
                        $head->linkCss($url);
                        break;
                }
            }

            foreach ($this->__styles as $url) {
                $head->linkCss($url);
            }
            foreach ($this->__scripts as [$url, $attributes]) {
                $head->linkScript($url, $attributes);
            }
        }
    }

    /**
     * Called when page is initialized. At this point no controls are populated.
     * You can override this method in your page implementation for initialization
     * purposes.
     */
    protected function Page_Init(TActionArgs $args): void
    {
    }

    /**
     * Called when page is loaded. At this point all controls are populated,
     * viewstate is restored and user events (postback) are handled, however
     * no action has been called yet (if requested).
     */
    protected function Page_Load(TActionArgs $args): void
    {
    }
}
