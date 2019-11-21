<?php

class Country
{
    public $id;
    public $name;
    public $code;
    public $regions;

    public function __construct($id, $name, $code, $regions)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->regions = $regions;
    }
}
