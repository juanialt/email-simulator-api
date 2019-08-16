<?php

class Label
{
    public $id;
    public $userId;
    public $name;

    public function __construct($id, $userId, $name)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
    }
}
