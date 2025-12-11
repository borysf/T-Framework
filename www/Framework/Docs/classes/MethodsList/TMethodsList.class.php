<?php
namespace Docs\classes\MethodsList;

use Docs\classes\DocComment;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\Template\TTemplatedControl;

class TMethodsList extends TTemplatedControl {
    private ReflectionClass $_ref;

    private const NO_RETURN_TYPES = ['__construct', '__destruct'];

    #[Prop]
    public string $title = '';

    public function display(ReflectionClass $ref, callable $filter) : void {
        $this->_ref = $ref;

        $data = $this->__getMethodsFiltered($ref, $filter);
        $this->Title->text = $this->title;
        $this->visible = !empty($data);
        $this->List->dataSource = $data;
        $this->List->dataBind();
    }

    protected function List_DataBind(TRepeater $sender, TDataBindEventArgs $args) : void {
        if (($name = $args->data->getName()) && !in_array($name, $this::NO_RETURN_TYPES)) {
            $returnType = $args->data->getReturnType();
            $returnType = $returnType ?: 'mixed';
            $args->item->Type->display($returnType);
            $args->item->TypePrefix->show();
        }

        $args->item->Attributes->display($args->data);
        $args->item->Modifiers->text = implode(' ', Reflection::getModifierNames($args->data->getModifiers()));
        $args->item->Name->text = $name;
        $args->item->Comment->comment = new DocComment($this->_ref, $args->data);
        $args->item->Arguments->display($args->data);

        // if ($args->data->hasDefaultValue()) {
        //     $args->item->ValueContainer->show();
        //     $args->item->Value->text = json_encode($args->data->getDefaultValue());
        //     $args->item->Value->html->class->add(gettype($args->data->getDefaultValue()));
        // }

        if (($declaringClass = $args->data->getDeclaringClass()->getName()) != $this->_ref->getName()) {
            $args->item->InheritedFrom->visible = true;
            $args->item->InheritedFromLink->text = $this->__toShortName($declaringClass);
            
            if (strpos($declaringClass, '\\')) {
                $args->item->InheritedFromLink->navigateUrl = ['system:docs:path', [
                    'path' => str_replace('\\', '.', $declaringClass)
                ]];
            }
        }

        $path = strtr($declaringClass, '\\', '.');
        
        // if (strpos($returnType, '\\')) {
        //     $args->item->Type->navigateUrl = ['system:docs:path', [
        //         'path' => $path,
        //     ]];
        // }

        $args->item->Name->navigateUrl = ['system:docs:method', [
            'path' => $path,
            'name' => $args->data->getName()
        ]];

    }

    private function __getMethodsFiltered(ReflectionClass $ref, callable $filter) : array {
        $ret = [];
        foreach ($ref->getMethods() as $property) {
            if ($filter($property)) {
                $ret[] = $property;
            }
        }
        
        usort($ret, fn(ReflectionMethod $a, ReflectionMethod $b) => strcmp($a->getName(), $b->getName()));

        return $ret;
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