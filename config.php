<?php
error_reporting(E_ERROR);
session_start();
include_once "./lib/constants.php";
include_once "./lib/lodash.php";
include_once "./lib/class.Utils.php";
include_once "db.php";

include_once "./model/class.User.php";
include_once "./model/class.Message.php";
include_once "./model/class.Label.php";
include_once "./model/class.Attachment.php";
include_once "./model/class.Country.php";
include_once "./model/class.Region.php";
