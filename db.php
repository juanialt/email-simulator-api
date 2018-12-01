<?php
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$conn->set_charset('utf8mb4');
$conn->autocommit(TRUE);

if ($conn->connect_error) {
    die("Fallo la conexion por " . $conn->connect_error);
    return;
}