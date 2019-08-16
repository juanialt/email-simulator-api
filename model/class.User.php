<?php

class User
{
    public $id;
    public $username;
    public $firstname;
    public $lastname;
    public $address;
    public $phone;
    public $countryId;
    public $regionId;
    public $city;
    public $email;

    public function __construct($id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email)
    {
        $this->id = $id;
        $this->username = $username;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->address = $address;
        $this->phone = $phone;
        $this->countryId = $countryId;
        $this->regionId = $regionId;
        $this->city = $city;
        $this->email = $email;
    }
}
