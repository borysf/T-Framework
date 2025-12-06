<?php
namespace Docs\classes\TypesList;

use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Template\TTemplatedControl;

class TTypesList extends TTemplatedControl {
    public function display(?string $types) : void {
        $this->List->dataSource = explode('|', $types === null ? 'mixed' : $types);
        $this->List->dataBind();
    }

    protected function List_DataBind(TRepeater $repeater, TDataBindEventArgs $args) : void {
        $type = ltrim($args->data, '?');

        if (strpos($type, '\\')) {
            $args->item->Type->navigateUrl = ['system:docs:path', [
                'path' => strtr($type, '\\', '.')
            ]];
        } else {
            $args->item->Type->html->class->add($type);
        }

        $args->item->Type->text = $this->__toShortName($args->data);
    }

    private function __toShortName(?string $name) : string {
        if ($name === null) {
            return 'null';
        }

        $pos = strrpos($name, '\\');

        $first = substr($name, 0, 1);

        if ($pos === false) {
            return $name;
        }

        return ($first == '?' ? '?' : '').substr($name, $pos + 1);
    }
}