<?php
namespace System\DataSource;

use System\Web\Page\Control\Event\TEventArgs;

class TDataSourceEventArgs extends TEventArgs {
    public readonly ?int $index;
    public readonly mixed $data;

    public function __construct(?int $index, mixed $data) {
        $this->index = $index;
        $this->data = $data;
    }
}