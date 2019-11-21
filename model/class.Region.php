<?php

class Region
{
    public $id;
    public $name;
    public $code;

    public function __construct($id, $name, $code)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
    }
}
