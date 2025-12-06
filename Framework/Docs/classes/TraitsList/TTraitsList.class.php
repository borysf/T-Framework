<?php
namespace Docs\classes\TraitsList;

use Docs\classes\DocComment;
use Reflection;
use ReflectionClass;
use ReflectionClassConstant;
use System\Web\Page\Control\Core\DataBound\TDataBindEventArgs;
use System\Web\Page\Control\Core\TRepeater;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\Template\TTemplatedControl;

class TTraitsList extends TTemplatedControl {
    private ReflectionClass $_ref;

    #[Prop]
    public string $title = '';

    public function display(ReflectionClass $ref, ?callable $filter = null) : void {
        $this->_ref = $ref;

        $data = $ref->getTraitNames();
        $this->visible = !empty($data);
        $this->List->dataSource = $data;
        $this->List->dataBind();
    }

    protected function List_DataBind(TRepeater $sender, TDataBindEventArgs $args) : void {
        $args->item->TraitName->text = $this->__toShortName($args->data);

        if (strpos($args->data, '\\')) {
            $args->item->TraitName->navigateUrl = ['system:docs:path', ['path' => strtr($args->data, '\\', '.')]];
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
}