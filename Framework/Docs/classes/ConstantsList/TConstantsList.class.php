<?php
namespace Docs\classes\ConstantsList;

use Docs\classes\DocComment;
use Reflection;
use ReflectionClass;
use ReflectionClassConstant;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\Template\TTemplatedControl;

class TConstantsList extends TTemplatedControl {
    private ReflectionClass $_ref;

    #[Prop]
    public string $title = '';

    public function display(ReflectionClass $ref, ?callable $filter = null) : void {
        $this->_ref = $ref;

        $data = $this->__getConstantsFiltered($ref, $filter);
        $this->Title->text = $this->title;
        $this->visible = !empty($data);
        $this->List->dataSource = $data;
        $this->List->dataBind();
    }

    protected function List_DataBind(TRepeater $sender, TDataBindEventArgs $args) : void {
        $args->item->Modifiers->text = implode(' ', Reflection::getModifierNames($args->data->getModifiers()));
        $args->item->ConstantName->text = $args->data->getName();
        $args->item->ConstantValue->text = $this->__toDisplayValue($args->data->getValue());
        $args->item->ConstantValue->html->class->add(gettype($args->data->getValue()));
        $args->item->Comment->comment = new DocComment($this->_ref, $args->data);

        if (($declaringClass = $args->data->getDeclaringClass()->getName()) != $this->_ref->getName()) {
            $args->item->InheritedFrom->visible = true;
            $args->item->InheritedFromLink->text = $this->__toShortName($declaringClass);

            if (strpos($declaringClass, '\\')) {
                $args->item->InheritedFromLink->navigateUrl = ['system:docs:path', [
                    'path' => strtr($declaringClass, '\\', '.')
                ]];
            }
        }
    }

    private function __toShortName(string $name) : string {
        $pos = strrpos($name, '\\');

        $first = substr($name, 0, 1);

        if ($pos === false) {
            return $name;
        }

        return ($first == '?' ? '?' : '').substr($name, $pos + 1);
    }

    private function __toDisplayValue(mixed $value) : string {
        return str_replace('\\\\', '\\', json_encode($value));
    }

    private function __getConstantsFiltered(ReflectionClass $ref, ?callable $filter) : array {
        $ret = [];
        foreach ($ref->getReflectionConstants() as $constant) {
            if ($filter === null || $filter($constant)) {
                $ret[] = $constant;
            }
        }
        
        usort($ret, fn(ReflectionClassConstant $a, ReflectionClassConstant $b) => strcmp($a->getName(), $b->getName()));

        return $ret;
    }
}