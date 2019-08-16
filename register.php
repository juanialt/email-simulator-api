<?php
include_once "./config.php";
header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
}

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
        http_response_code(400);
        $error = new stdClass();
        $error->errorMessage = "el usuario ya existe";
        echo json_encode($error);
        exit;
    }
} else {
    http_response_code(400);
    $error = new stdClass();
    $error->errorMessage = "se requieren todos los datos solicitados";
    echo json_encode($error);
    exit;
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

function addUser2($username, $firstname, $lastname, $password, $address, $phone, $email, $city, $regionId, $countryId)
{
    global $conn;

    // ADD A NEW USER
    $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, password, address, phone, email, city, region_id, country_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssii", $username, $firstname, $lastname, $password, $address, $phone, $email, $city, $regionId, $countryId);
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    return true;
}
