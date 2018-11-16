<?php
include_once "./config.php";
header('Content-Type: application/json');
// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
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

if (isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $user = checkUser($username, $password);

    if ($user) {
        $_SESSION["user"] = serialize($user);
        http_response_code(200);
        echo json_encode($user);
    } else {
        http_response_code(401);
        $error = new stdClass();
        $error->errorMessage = "credenciales incorrectas";
        echo json_encode($error);
        exit;
    }
} else {
    http_response_code(400);
    $error = new stdClass();
    $error->errorMessage = "se requiere username y password";
    echo json_encode($error);
}

function checkUser($username, $password)
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT user_id, username, firstname, lastname FROM users WHERE username = ? AND password = ?")) {
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $firstname, $lastname);

        if ($stmt->fetch()) {
            $user = new User($user_id, $username, $firstname, $lastname);
            $stmt->close();
            return $user;
        }
    }
    return false;
}
