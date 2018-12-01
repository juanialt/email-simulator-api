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
            exit;
        }

        if (isset($_GET["folder"])) {

            if (isset($_SESSION["user"])) {

                $folderName = $_GET["folder"];
                $user = unserialize($_SESSION["user"]);

                switch ($folderName) {
                    case "INBOX":{
                            $messages = getUserReceivedMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                    case "SENT":{
                            $messages = getUserSentMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                    default:{
                            $messages = getUserSentMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                }
            } else {
                echo "NO SESSSION";
            }
        }
        break;
    case "POST":
        if (isset($_SESSION["user"])) {
            if (isset($_POST["from"]) && isset($_POST["to"])) {
                $user = unserialize($_SESSION["user"]);
                $from = $user->id;
                $to = $_POST["to"];
                $subject = $_POST["subject"];
                $htmlCode = $_POST["htmlCode"];
                $date = date('Y-m-d H:i:s');

                $message = new Message(null, $from, $subject, $htmlCode, $date);
                $message = addMessage($message, $to);

                http_response_code(200);
                echo json_encode($message);
            }
        } else {
            echo "NO SESSION";
        }
        break;

}

function addMessage($message, $recipientId)
{
    global $conn;

    // ADD A NEW MESSAGE
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, subject, message, date) VALUES (?, ?, ?, ?)");
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

function getUserInbox($userId)
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT * FROM messages WHERE sender_id = ?")) {
        $stmt->bind_param("i", $senderId);
        $stmt->execute();
        $stmt->bind_result($messageId, $senderId, $subject, $message, $date);

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            array_push($messages, $message);
        }
        return $messages;
    }
    return null;
}

function getUserReceivedMessages($recipientId)
{
    global $conn;
    $query = "SELECT
        messages.message_id,
        messages.subject,
        messages.message,
        messages.date,
        recipients.recipient_id,
        users.username AS recipient_username,
        users.firstname AS recipient_firstname,
        users.lastname AS recipient_lastname,
        messages.sender_id,
        u1.username AS sender_username,
        u1.firstname AS sender_firstname,
        u1.lastname AS sender_lastname
    FROM
        messages
            INNER JOIN
        recipients ON recipients.message_id = messages.message_id
            INNER JOIN
        users ON users.user_id = recipients.recipient_id
            LEFT JOIN
        users AS u1 ON u1.user_id = messages.sender_id
    WHERE
        recipients.recipient_id = ?
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $recipientId);
        $stmt->execute();
        $stmt->bind_result(
            $messageId,
            $subject,
            $message,
            $date,
            $recipientId,
            $recipientUsername,
            $recipientFirstname,
            $recipientLastname,
            $senderId,
            $senderUsername,
            $senderFirstname,
            $senderLastname
        );

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            $message->recipientId = $recipientId;
            $message->recipientUsername = $recipientUsername;
            $message->recipientFirstname = $recipientFirstname;
            $message->recipientLastname = $recipientLastname;
            $message->senderUsername = $senderUsername;
            $message->senderFirstname = $senderFirstname;
            $message->senderLastname = $senderLastname;
            array_push($messages, $message);
        }
        return $messages;
    }
    return null;
}

function getUserSentMessages($senderId)
{
    global $conn;
    $query = "SELECT
        message_id,
        sender_id,
        subject,
        message,
        date,
        firstname as sender_name,
        lastname as sender_lastname
    FROM
        messages
        INNER JOIN
            users ON messages.sender_id = users.user_id
    WHERE
        sender_id = ?
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $senderId);
        $stmt->execute();
        $stmt->bind_result($messageId, $senderId, $subject, $message, $date, $senderFirstname, $senderLastname);

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            $message->senderFirstname = $senderFirstname;
            $message->senderLastname = $senderLastname;
            array_push($messages, $message);
        }
        return $messages;
    }
    return null;
}
