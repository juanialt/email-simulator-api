<?php

class User
{
    public $id;
    public $username;
    public $firstname;
    public $lastname;

    public function __construct($id, $username, $firstname, $lastname)
    {
        $this->id = $id;
        $this->username = $username;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }
}
