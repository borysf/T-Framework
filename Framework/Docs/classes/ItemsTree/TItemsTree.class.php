<?php
namespace Docs\classes\ItemsTree;

use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Template\TTemplatedControl;

class TItemsTree extends TTemplatedControl {
    public function setData(array $data) : void {
        $this->Items->dataSource = $data;
        $this->Items->dataBind();
    }
    
    protected function Items_DataBind(TRepeater $sender, TDataBindEventArgs $args) : void {
        $text = ($pos = strpos($args->index, '.')) !== false ? substr($args->index, 0, $pos) : $args->index;
        $args->item->ItemName->text = $text;

        $args->item->ItemName->navigateUrl = ['system:docs:path', [
            'path' => $args->data['path']
        ]];

        if (isset($args->data['sub'])) {
            $args->item->ListItem->html->class = 'node';
            $args->item->SubItems->setData($args->data['sub']);
        } else {
            $args->item->ItemExt->text = '.'.$args->data['ext'];
            $args->item->ListItem->html->class = 'leaf '.strtr($args->data['ext'], '.', '-');
            $args->item->SubItems->visible = false;
        }
    }
}