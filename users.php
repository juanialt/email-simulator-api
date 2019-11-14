<?php
include_once "./config.php";
include_once "./headers.php";

$requestMethod = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION["user"])) {
    logoutAndExit();
}

switch ($requestMethod) {
    case "GET":
        if (isset($_GET["id"])) {
            $userId = $_GET["id"];
            $user = getUser($userId);

            if ($user != null) {
                http_response_code(200);
                echo json_encode($user);
                exit();
            } else {
                showError("no se encuentra usuario con ese ID", 400, $e);
            }
        } else {
            $users = getUsers();
            http_response_code(200);
            echo json_encode($users);
            exit();
        }
        break;
}

function getUser($userId)
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT user_id, username, firstname, lastname, address, phone, country_id, region_id, city, email FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);

        if ($stmt->fetch()) {
            $user = new User($user_id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);
            $stmt->close();
            return $user;
        }
    }
    return null;
}

function getUsers()
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT user_id, username, firstname, lastname, address, phone, country_id, region_id, city, email FROM users")) {
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);

        $users = array();
        while ($stmt->fetch()) {
            $user = new User($user_id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);
            array_push($users, $user);
        }
        return $users;
    }
    return null;
}
