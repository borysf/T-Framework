<?php
namespace System\DataSource;

class TDataSourceRow {
    public readonly int $key;
    public readonly mixed $data;

    public function __construct(mixed $data, int $key) {
        $this->key = $key;
        $this->data = $data;
    }
}