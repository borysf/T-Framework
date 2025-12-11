<?php
namespace Docs\examples;

use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\Core\Form\TButton;
use System\Web\Page\Control\Core\Form\TButtonEventArgs;
use System\Web\Page\TPage;

class viewstateButtonIncrement extends TPage {
    #[Stateful]
    protected int $clickCount = 0;

    protected function Button_onClick(TButton $sender, TButtonEventArgs $args) : void {
        $sender->text = 'Click: '.(++$this->clickCount);
    }
}