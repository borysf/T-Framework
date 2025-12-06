<?php
namespace Docs\classes\AttributesList;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Template\TTemplatedControl;

class TAttributesList extends TTemplatedControl {
    public function display(ReflectionClass|ReflectionMethod|ReflectionProperty $ref) {
        if (!empty($attributes = $ref->getAttributes())) {
            $this->List->dataSource = $attributes;
            $this->List->dataBind();
            $this->visible = true;
        } else {
            $this->visible = false;
        }
    }

    protected function List_DataBind(TRepeater $sender, TDataBindEventArgs $args) {
        $name = $args->data->getName();

        $args->item->Name->text = $this->__toShortName($name);
        $args->item->Args->dataSource = $args->data->getArguments();
        $args->item->Args->dataBind();

        if (strpos($name, '\\')) {
            $args->item->Name->navigateUrl = ['system:docs:path', [
                'path' => strtr($name, '\\', '.')
            ]];
        }
    }

    protected function Args_DataBind(TRepeater $sender, TDataBindEventArgs $args) {
        if (!is_numeric($args->index)) {
            $args->item->ArgColon->visible = true;
            $args->item->ArgName->visible = true;
            $args->item->ArgName->text = $args->index;
        }
        $args->item->ArgValue->text = str_replace('\/', '/', json_encode($args->data));
        $args->item->ArgValue->html->class->add(gettype($args->data));
    }

    private function __toShortName(string $name) : string {
        $pos = strrpos($name, '\\');

        $first = substr($name, 0, 1);

        if ($pos === false) {
            return $name;
        }

        return ($first == '?' ? '?' : '').substr($name, $pos + 1);
    }
}