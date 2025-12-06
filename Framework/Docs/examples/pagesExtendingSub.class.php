<?php 
namespace Docs\examples;

use System\Web\Action\TActionArgs;

class pagesExtendingSub extends pagesExtendingBase {
    protected function Page_Load(TActionArgs $args) : void {
        $this->Header->text = 'Text from inheriting page!';
        $this->Name->text = 'John Doe';
    }
}