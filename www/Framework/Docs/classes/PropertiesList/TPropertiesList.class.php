<?php
namespace Docs\classes\PropertiesList;

use Docs\classes\DocComment;
use Reflection;
use ReflectionClass;
use ReflectionProperty;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\Template\TTemplatedControl;

class TPropertiesList extends TTemplatedControl {
    private ReflectionClass $_ref;

    #[Prop]
    public string $title = '';

    public function display(ReflectionClass $ref, callable $filter) : void{
        $this->_ref = $ref;

        $data = $this->__getPropertiesFiltered($ref, $filter);
        $this->Title->text = $this->title;
        $this->visible = !empty($data);
        $this->List->dataSource = $data;
        $this->List->dataBind();
    }

    protected function List_DataBind(TRepeater $sender, TDataBindEventArgs $args) : void {
        $args->item->Modifiers->text = implode(' ', Reflection::getModifierNames($args->data->getModifiers())).($args->data->isReadOnly() ? ' readonly' : '');
        $args->item->Type->display($args->data->getType());
        $args->item->Name->text = $args->data->getName();
        $args->item->Comment->comment = new DocComment($this->_ref, $args->data);
        $args->item->Stateful->visible = !empty($args->data->getAttributes(Stateful::class));
        $args->item->Prop->visible = !empty($args->data->getAttributes(Prop::class));
        $args->item->Attributes->display($args->data);

        // if (strpos($args->data->getType(), '\\')) {
        //     $path = str_replace('\\', '.', ltrim($args->data->getType(), '?'));
        //     $args->item->Type->navigateUrl = ['system:docs:path', [
        //         'path' => $path,
        //     ]];
        //     $args->item->Name->navigateUrl = ['system:docs:property', [
        //         'path' => $path,
        //         'name' => $args->data->getName()
        //     ]];
        // }

        if ($args->data->hasDefaultValue()) {
            $args->item->ValueContainer->show();
            $args->item->Value->text = str_replace('\/', '/', json_encode($args->data->getDefaultValue()));
            $args->item->Value->html->class->add(gettype($args->data->getDefaultValue()));
        }

        if (($declaringClass = $args->data->getDeclaringClass()->getName()) != $this->_ref->getName()) {
            $args->item->InheritedFrom->visible = true;
            $args->item->InheritedFromLink->text = $this->__toShortName($declaringClass);

            if (strpos($declaringClass, '\\')) {
                $args->item->InheritedFromLink->navigateUrl = ['system:docs:path', [
                    'path' => str_replace('\\', '.', $declaringClass)
                ]];
            }
        }
    }

    private function __getPropertiesFiltered(ReflectionClass $ref, callable $filter) : array {
        $ret = [];
        foreach ($ref->getProperties() as $property) {
            if ($filter($property)) {
                $ret[] = $property;
            }
        }
        
        usort($ret, fn(ReflectionProperty $a, ReflectionProperty $b) => strcmp($a->getName(), $b->getName()));

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