<?php
namespace Docs\errorPage;

use Docs\master\master;
use System\Web\Action\Action;
use System\Web\Action\TActionArgs;

class errorPage extends master {
    #[Action(name: 'handle404')]
    public function handle404_action(TActionArgs $args) {
        $this->Header->text = 'Oops! We could not find the page you are looking for.';
        $this->Message->text = 'If you get in here from documentation link, this means this part of documentation is not ready yet.';
    }

    #[Action(name: 'handle500')]
    public function handle500_action(TActionArgs $args) {
        $this->Header->text = 'Oops! Something messed up!';
        $this->Message->text = 'Most probably some exception has been thrown.';
    }
}