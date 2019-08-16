<?php
include_once "./config.php";
include_once "./lodash.php";

use function _\split;

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
                            $messages = getUserMessages($user->id);
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
                $to = split($_POST["to"], ",");
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

function addMessage($message, $recipients)
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

    // LINK THE MESSAGE WITH THE RECIPIENTS
    foreach ($recipients as $recipient => $recipientId) {
        $stmt = $conn->prepare("INSERT INTO recipients (recipient_id, message_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $recipientId, $message->id);
        $stmt->execute();
    }

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    return $message;
}

// function getUserInbox($userId)
// {
//     global $conn;
//     if ($stmt = $conn->prepare("SELECT * FROM messages WHERE sender_id = ?")) {
//         $stmt->bind_param("i", $senderId);
//         $stmt->execute();
//         $stmt->bind_result($messageId, $senderId, $subject, $message, $date);

//         $messages = array();
//         while ($stmt->fetch()) {
//             $message = new Message($messageId, $senderId, $subject, $message, $date);
//             array_push($messages, $message);
//         }
//         return $messages;
//     }
//     return null;
// }

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

function getUserSentMessages2($senderId)
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

            // $recipients = getRecipientsByMessageId($messageId);
            // $message->recipients = $recipients;
            array_push($messages, $message);
        }

        foreach ($messages as $message) {
            // var_dump($message->id);
            // var_dump(getRecipientsByMessageId(55));

            $message->recipients = getRecipientsByMessageId($message->id);
        }

        // var_dump(getRecipientsByMessageId(55));

        return $messages;
    }
    return null;
}

function getUserSentMessages($senderId)
{
    global $conn;
    $query = "SELECT
        messages.message_id,
        messages.sender_id,
        messages.subject,
        messages.message,
        messages.date,
        recipients.recipient_id,
        users.firstname,
        users.lastname,
        users.username
    FROM
        messages
            INNER JOIN
        recipients
            INNER JOIN
        users ON users.user_id = recipients.recipient_id
    WHERE
        messages.message_id = recipients.message_id
        AND
        messages.sender_id = ?
    ORDER BY message_id;
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $senderId);
        $stmt->execute();
        $stmt->bind_result(
            $messageId,
            $senderId,
            $subject,
            $message,
            $date,
            $recipientId,
            $recipientFirstName,
            $recipientLastName,
            $recipientUsername
        );

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            $message->recipientId = $recipientId;
            $message->recipientUsername = $recipientUsername;
            array_push($messages, $message);
        }

        $newArray = array();
        $i = 0;
        while ($i < count($messages)) {
            $newMessage = new Message(
                $messages[$i]->id, 
                $messages[$i]->senderId, 
                $messages[$i]->subject,
                $messages[$i]->message,
                $messages[$i]->date
            );

            $recipients = array();
            while ($i < count($messages) && $messages[$i]->id == $newMessage->id) {
                $recipient = new StdClass();
                $recipient->id = $messages[$i]->recipientId;
                array_push($recipients, $recipient);
                $i = $i + 1;
            }

            $newMessage->recipients = $recipients;
            array_push($newArray, $newMessage);
        }

        return $newArray;
    }
    return null;
}

function getRecipientsByMessageId($messageId)
{
    global $conn;
    $query = "SELECT
        recipients.recipient_id,
        users.username,
        users.firstname,
        users.lastname
    FROM
        recipients
            INNER JOIN
        users ON users.user_id = recipients.recipient_id
    WHERE
        message_id = ?;
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $stmt->bind_result($recipientId, $username, $firstname, $lastname);

        $recipients = array();
        while ($stmt->fetch()) {
            $recipient->id = $recipientId;
            $recipient->username = $username;
            array_push($recipients, $recipient);
        }
        return $recipients;
    }
    return null;
}

function getUserMessages($userId)
{
    global $conn;
    $query = "SELECT
        messages.message_id,
        messages.subject,
        messages.message,
        messages.date,
    FROM
        messages
    WHERE
        messages.message_id = ?
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($messageId, $subject, $message, $date);

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            array_push($messages, $message);
        }
        return $messages;
    }
    return null;
}
