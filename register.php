<?php
include_once "./config.php";
include_once "./headers.php";

$username = $_POST["username"];
$firstname = $_POST["name"];
$lastname = $_POST["lastName"];
$address = $_POST["address"];
$phone = $_POST["phone"];
$countryId = $_POST["countryId"];
$regionId = $_POST["stateId"];
$city = $_POST["city"];
$email = $_POST["email"];
$password = $_POST["password"];

if (isset($username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email, $password)) {
    if (!checkUsername($username)) {
        $user = new User(null, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);
        $user = addUser($user, $password);

        http_response_code(200);
        echo json_encode($user);
        exit;
    } else {
        showError("el usuario ya existe", 400, $e);
    }
} else {
    showError("se requieren todos los datos solicitados", 400, $e);
}

function checkUsername($username)
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT username FROM users WHERE username = ?")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();

        if ($stmt->fetch()) {
            $stmt->close();
            return true;
        }
    }
    return false;
}

function addUser($user, $password)
{
    global $conn;

    $md5Password = md5($password);
    $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, password, address, phone, email, city, region_id, country_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssii",
        $user->username,
        $user->firstname,
        $user->lastname,
        $md5Password,
        $user->address,
        $user->phone,
        $user->email,
        $user->city,
        $user->regionId,
        $user->countryId
    );
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    $user->id = $conn->insert_id;
    return $user;
}
