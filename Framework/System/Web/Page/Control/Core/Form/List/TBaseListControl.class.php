<?php

namespace System\Web\Page\Control\Core\Form\List;

use System\Web\Page\Control\Core\DataBound\TDataBoundControl;
use System\Web\Page\Control\Core\Form\TFormControl;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\State\Stateful;

abstract class TBaseListControl extends TDataBoundControl
{
    use TFormControl;

    const HTML_TAG_NAME = 'select';
    const HTML_HAS_END_TAG = true;
    const CHILDREN_TYPES_ALLOW = [TListOption::class];

    #[Prop, Stateful]
    public ?string $dataTextField = null;

    #[Prop, Stateful]
    public ?string $dataValueField = null;

    #[Prop, Stateful]
    public ?string $dataClassField = null;

    protected function createItem(string|int $key, int $count, mixed $data, int|string $index, ?int $iteration): TListOption
    {
        return new TListOption([
            'key' => $key,
            'value' => $this->dataValueField ? $this->getValueFromData($data, $this->dataValueField) : $data,
            'text' => $this->dataTextField ? $this->getValueFromData($data, $this->dataTextField) : $data,
            'html.class' => $this->dataClassField ? $this->getValueFromData($data, $this->dataClassField) : '',
        ]);
    }

    protected function getValueFromData($data, $dataField)
    {
        return $data[$dataField];
    }

    protected function shouldSelectOption(TListOption $option)
    {
        return false;
    }

    protected function renderContents(): void
    {
        foreach ($this->getControls() as $child) {
            $child->render();
        }
    }

    protected function onRender(?TEventArgs $args): void
    {
        $this->setHtmlName();

        foreach ($this->getControls() as $option) {
            $option->selected = $this->shouldSelectOption($option);
        }

        parent::onRender($args);
    }
}
