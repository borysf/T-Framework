<?php
namespace System\DataSource;

interface ISelectionSource 
{
    public function select(int $key) : bool;
    public function deselect(int $key) : bool;
    public function isSelected(int $key) : bool; 
    public function getSelected() : array;
}