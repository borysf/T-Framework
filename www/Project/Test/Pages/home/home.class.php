<?php
namespace Project\Test\Pages\home;

use Project\Test\Pages\master\master;
use System\DataSource\TDataSource;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\Form\TButton;
use System\Web\Page\Control\Core\Form\TButtonEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Action\TActionArgs;
use System\Web\Page\Control\State\Stateful;

class home extends master {
    #[Stateful] 
    protected int $clickCount = 0;

    protected function Page_Init(TActionArgs $args) : void {}

    protected function Page_Load(TActionArgs $args) : void {
        if (!$this->DropDown->dataBound) {
            $this->DropDown->dataSource = ['foo', 'bar', 'baz'];
            $this->DropDown->dataBind();
        }

        if (!$this->MultiSelect->dataBound) {
            $this->MultiSelect->dataSource = ['foo', 'bar', 'baz'];
            $this->MultiSelect->dataBind();
        }

        $this->Rows->showHeaderWhenEmpty = $this->ShowHeaderFooterCheckBox->checked;
        $this->Rows->showFooterWhenEmpty = $this->ShowHeaderFooterCheckBox->checked;
    }

    protected function Button_Count_Clicked(TButton $sender, TButtonEventArgs $args) {
        $sender->text = 'clicked: '.(++$this->clickCount);
    }

    protected function Button1_Clicked(TButton $sender, TButtonEventArgs $args) {
        $this->MainViews->activeViewIndex = $args->data;
    }

    protected function Button2_Clicked(TButton $sender, TButtonEventArgs $args) {
        $this->MainViews->activeViewIndex = $args->data;

        if (!$this->Rows->dataBound) {
            $this->Rows->dataSource = new TDataSource(['foo','bar','baz']);
            $this->Rows->dataBind();
        }
    }

    protected function Rows_DataBind(TRepeater $sender, TDataBindEventArgs $args) {
        $args->item->Input->text = $args->item->Input->getSystemId().' ('.$args->data.') #'.$args->key;
        $args->item->Button->key = $args->key;
        $args->item->DeleteButton->key = $args->key;
        $args->item->Checkbox->key = $args->key;
    }

    protected function Input_ToggleEdit(TButton $sender, TButtonEventArgs $args) {
        $this->{$args->data}->editMode = !$this->{$args->data}->editMode;
        $sender->text = $this->{$args->data}->editMode ? 'Apply' : 'Edit';
    }

    protected function Rows_Button_Click(TButton $sender, TButtonEventArgs $args) {
        $sender->text = 'clicked! ('.$sender->key.')';
    }

    protected function Rows_Button_Delete_Click(TButton $sender, TButtonEventArgs $args) {
        $this->Rows->dataSource->remove($sender->key);
    }

    protected function Add_First_Clicked(TButton $sender) {
        $this->Rows->dataSource->add(time(), 0);
    }

    protected function Add_Last_Clicked(TButton $sender) {
        $this->Rows->dataSource->add(time());
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