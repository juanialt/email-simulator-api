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

$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case "GET":
        if (isset($_GET["id"])) {
            $userId = $_GET["id"];
            $user = getUser($userId);

            if ($user != null) {
                http_response_code(200);
                echo json_encode($user);
            } else {
                http_response_code(404);
            }
        } else {
            $session = unserialize($_SESSION["user"]);

            $messages = getMessagesBySenderId($session->id);
            http_response_code(200);
            echo json_encode($messages);
        }
        break;
    case "POST":
        break;
}

function getMessagesBySenderId($senderId)
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT * FROM messages WHERE sender_id = ?")) {
        $stmt->bind_param("i", $senderId);
        $stmt->execute();
        $stmt->bind_result($message_id, $sender_id, $subject, $message, $date);

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($message_id, $sender_id, $subject, $message, $date);
            array_push($messages, $message);
        }
        return $messages;
    }
    return null;
}
