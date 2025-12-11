<?php
namespace Docs\classes\ArgumentsList;

use ReflectionMethod;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Template\TTemplatedControl;

class TArgumentsList extends TTemplatedControl {
    public function display(ReflectionMethod $method) {
        $this->List->dataSource = $method->getParameters();
        $this->List->dataBind();
    }

    protected function List_DataBind(TRepeater $sender, TDataBindEventArgs $args) {
        $args->item->Type->display($args->data->getType());
        $args->item->Name->text = $args->data->getName();
        $args->item->Variadic->visible = $args->data->isVariadic();

        if ($args->data->isDefaultValueAvailable()) {
            $args->item->Default->visible = true;

            if ($args->data->isDefaultValueConstant()) {
                $value = $args->data->getDefaultValueConstantName();
                $args->item->Value->text = $value;
                $args->item->Value->html->class->add('const');
            } else {
                $value = $args->data->getDefaultValue();
                $args->item->Value->text = str_replace('\/', '/', json_encode($value));
                $args->item->Value->html->class->add(gettype($value));
            }
        }
    }

    private function __toDotName(string $value) : string {
        return strtr($value, '\\', '.');
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