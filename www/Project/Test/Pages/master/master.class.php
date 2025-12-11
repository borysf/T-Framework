<?php
namespace Project\Test\Pages\master;

use System\Web\Page\Control\Core\Form\TButton;
use System\Web\Page\Control\Core\Form\TButtonEventArgs;
use System\Web\Page\TPage;
use System\Web\Action\TActionArgs;

class master extends TPage {
    protected function Master_Button_Click(TButton $sender, TButtonEventArgs $args) {
        $sender->text = "ya clicked ma! ({$this->Input->text})";
    }
}