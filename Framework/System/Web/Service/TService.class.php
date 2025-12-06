<?php
namespace System\Web\Service;

use System\Component\TComponent;
use System\TApplication;
use System\Web\Action\TActionArgs;

/**
 * Base class for all services. Service difers from a Page in such a way that it does not provide any 
 * templates. You can use services to create HTTP APIs.
 */
abstract class TService extends TComponent {
    /** References current application. */
    public readonly TApplication $app;

    final public function __construct(TActionArgs $args, TApplication $application) {
        $this->app = $application;

        $this->Service_Init($args);
        $this->Service_Load($args);
    }

    final public function run() {}

    /** Called when service is in initialization state. You should override this method to initialize your service. */
    protected function Service_Init(TActionArgs $args) : void {}

    /** Called when service is in loading state. You should override this method to load your service. */
    protected function Service_Load(TActionArgs $args) : void {}
}