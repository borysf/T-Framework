<?php
namespace System\Web\Page\Control\Core\DataBound;

use System\DataSource\TDataSource;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\TControl;

class TDataBindEventArgs extends TEventArgs {
    public readonly ?TControl $item;
    public readonly TDataSource|iterable $dataSource;
    public readonly mixed $data;
    public readonly string|int|null $key;
    public readonly ?int $count;
    public readonly string|int|null $index;
    public readonly ?int $iteration;

    public function __construct(TDataSource|iterable $dataSource, ?TControl $item = null, mixed &$data = null, string|int|null &$key = null, ?int &$count = null, string|int|null &$index = null, ?int &$iteration = null) {
        $this->item = $item;
        $this->data = $data;
        $this->key = $key;
        $this->count = $count;
        $this->index = $index;
        $this->iteration = $iteration;
        $this->dataSource = $dataSource;
    }
}