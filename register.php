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
$name = $_POST["name"];
$lastName = $_POST["lastName"];
$address = $_POST["address"];
$phone = $_POST["phone"];
$countryId = $_POST["countryId"];
$stateId = $_POST["stateId"];
$city = $_POST["city"];
$email = $_POST["email"];
$password = $_POST["password"];

if (isset($username, $name, $lastName, $address, $phone, $countryId, $stateId, $city, $email, $password)) {
    $user = checkUser($username);

    if (!$user) {
        http_response_code(200);
        // echo json_encode($user);
        echo "crear nuevo user";
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

function checkUser($username)
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

function addUser($username, $name, $lastName, $address, $phone, $countryId, $stateId, $city, $email, $password)
{
    global $conn;

    // ADD A NEW MESSAGE
    $stmt = $conn->prepare("INSERT INTO users (sender_id, subject, message, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $message->senderId, $message->subject, $message->message, $message->date);
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }
    $message->id = $conn->insert_id;

    // LINK THE MESSAGE WITH THE RECIPIENT
    $stmt = $conn->prepare("INSERT INTO recipients (recipient_id, message_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $recipientId, $message->id);
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    return $message;
}
