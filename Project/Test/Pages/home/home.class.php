<?php
namespace Project\Test\Pages\home;

use Project\Test\Pages\master\master;
use System\DataSource\TDataSource;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\Form\TButton;
use System\Web\Page\Control\Core\Form\TButtonEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Action\TActionArgs;

class home extends master {
    protected function Page_Init(TActionArgs $args) : void {}

    protected function Page_Load(TActionArgs $args) : void {
        if (!isset($this->state->clickCount)) {
            $this->state->clickCount = 0;
        }
    }

    protected function Button_Clicked(TButton $sender, TButtonEventArgs $args) {
        $sender->text = 'clicked: '.(++$this->state->clickCount);
        $this->Test->show();
    }

    protected function Button2_Clicked(TButton $sender, TButtonEventArgs $args) {
        // $sender->text = $this->input1->text;
        $this->Test->hide();

        $this->Rows->dataSource = new TDataSource(['foo','bar','baz']);
        $this->Rows->dataBind();
    }

    protected function Rows_DataBind(TRepeater $sender, TDataBindEventArgs $args) {
        $args->item->Input->text = $args->item->Input->getSystemId().' ('.$args->data.') #'.$args->key;
        $args->item->Button->key = $args->key;
        $args->item->DeleteButton->key = $args->key;
    }

    protected function Rows_Button_Click(TButton $sender, TButtonEventArgs $args) {
        $sender->text = 'clicked! ('.$sender->key.')';
    }

    protected function Rows_Button_Delete_Click(TButton $sender, TButtonEventArgs $args) {
        $this->Rows->dataSource->remove($sender->key);
    }

    protected function Add_First_Clicked(TButton $sender) {
        $this->Rows->dataSource->add('grr', 0);
    }

    protected function Add_Last_Clicked(TButton $sender) {
        $this->Rows->dataSource->add('grr');
    }

    protected function Remove_First_Clicked(TButton $sender) {
        $this->Rows->dataSource->remove();
    }

    protected function Remove_Last_Clicked(TButton $sender) {
        $this->Rows->dataSource->remove(-1);
    }

    protected function Empty_Clicked(TButton $sender) {
        $this->Rows->dataSource->removeAll();
    }

    protected function Switch_View_Button_Click(TButton $sender, TButtonEventArgs $args) {
        $this->Views->activeViewIndex = $args->data;
    }

}