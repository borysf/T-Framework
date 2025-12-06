<?php 
namespace Docs\examples;

use System\Web\Page\TPage;
use System\Web\Action\TActionArgs;

class pagesExtendingBase extends TPage {
    protected function Page_Load(TActionArgs $args) : void {}
}