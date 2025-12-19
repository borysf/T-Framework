<?php
namespace System\Web\Page\Control\Core\DataBound;

use System\Web\Page\Control\Event\TEventArgs;
use System\DataSource\TDataSource;
use System\DataSource\TDataSourceEventArgs;
use System\Web\Page\Control\State\Stateful;
use System\Web\Page\Control\TControl;

abstract class TDataBoundControl extends TControl implements IDataBoundControl {
    #[stateful]
    public TDataSource|iterable $dataSource;

    #[stateful]
    public bool $dataBound = false;

    private ?TControl $__emptyItem = null;

    protected function itemsContainer(): TControl {
        return $this;
    }

    protected function __createEmptyItem(): ?TControl {
        $item = $this->createEmptyItem();

        if (!$item) {
            return null;
        }

        $this->__emptyItem = $item;

        $this->itemsContainer()->addControl($item);

        $args = new TDataBindEventArgs(
            dataSource: $this->dataSource,
            item: $this->getDataBindItem($item)
        );

        $this->raise('onEmptyDataBind', $args);
        $item->propagate('onMount');

        return $item;
    }

    protected function __createItem(int|string $key, int $count, mixed $data, int|string $index, ?int $iteration) : TControl {
        if ($this->__emptyItem) {
            $this->__emptyItem->remove();
            $this->__emptyItem = null;
        }

        $item = $this->createItem($key, $count, $data, $index, $iteration);

        $item->key = &$key;

        $this->itemsContainer()->addControl($item, is_int($index) ? $index : null);

        $args = new TDataBindEventArgs(
            dataSource: $this->dataSource,
            item: $this->getDataBindItem($item),
            data: $data,
            key: $key,
            count: $count,
            index: $index,
            iteration: $iteration
        );

        $this->raise('onItemDataBind', $args);
        $item->propagate('onMount');

        return $item;
    }

    protected function getDataBindItem(TControl $item) : TControl {
        return $item;
    }

    protected function createItem(string|int $key, int $count, mixed $data, int|string $index, ?int $iteration): TControl {
        throw new TDataBoundControlException($this, 'createItem() must be implemented');
    }

    protected function createEmptyItem(): ?TControl {
        throw new TDataBoundControlException($this, 'createEmptyItem() must be implemented');
    }

    public function dataBind(): void {
        if ($this->dataBound) {
            throw new TDataBoundControlException($this, 'Data already bound');
        }

        $this->dataBound = true;

        $this->__build();
    }

    protected function onRestoreStateComplete(?TEventArgs $args) : void {
        $this->__build();
    }

    protected function __build() : void {
        if ($this->dataBound) {
            $isDS = $this->dataSource instanceof TDataSource;

            if ($isDS) {
                $this->dataSource->on('onRemove', function (TDataSource $sender, TDataSourceEventArgs $args) {
                    $this->itemsContainer()->removeControlAtIndex($args->index);
                    $this->raise('onRemoveItem', $args);

                    if ($this->dataSource->count() == 0) {
                        $this->__createEmptyItem();
                    }
                });

                $this->dataSource->on('onAdd', function (TDataSource $sender, TDataSourceEventArgs $args) {
                    $this->__createItem($args->data->key, $this->dataSource->count(), $args->data->value, $args->index, $args->index === 0 ? 0 : $this->dataSource->count() - 1);
                    $this->raise('onAddItem', $args);
                });

                $this->dataSource->on('onRemoveAll', function (TDataSource $sender, TDataSourceEventArgs $args) {
                    $this->itemsContainer()->removeAllControls();
                    $this->__createEmptyItem();

                    $this->raise('onRemoveAll');
                });
            }

            $count = count($this->dataSource);
            $args = new TDataBindEventArgs(dataSource: $this->dataSource, count: $count);

            $this->raise('onDataBindBegin', $args);

            if ($count == 0) {
                $this->__createEmptyItem();
            } else {
                $i = 0;
                foreach ($this->dataSource as $k => $v) {
                    $this->__createItem($isDS ? $v->key : $k, $count, $isDS ? $v->value : $v, $k, $i++);
                }
            }
            $this->raise('onDataBindComplete', $args);
        }
    }

    protected function onAddItem(?TDataSourceEventArgs $args) : void {}
    protected function onRemoveItem(?TDataSourceEventArgs $args) : void {}
    protected function onRemoveAll(?TDataSourceEventArgs $args) : void {}

    protected function onDataBindBegin(TDataBindEventArgs $args) : void {}
    protected function onEmptyDataBind(TDataBindEventArgs $args) : void {}
    protected function onItemDataBind(TDataBindEventArgs $args) : void {}
    protected function onDataBindComplete(TDataBindEventArgs $args) : void {}
}
